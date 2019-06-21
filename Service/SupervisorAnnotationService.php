<?php

declare(strict_types=1);

/*
 * This file is part of the SupervisorBundle package.
 *
 * (c) Wakeapp <https://wakeapp.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wakeapp\Bundle\SupervisorBundle\Service;

use Doctrine\Common\Annotations\Reader;
use Francodacosta\Supervisord\Command;
use Francodacosta\Supervisord\Configuration;
use Francodacosta\Supervisord\Processors\CommandConfigurationProcessor;
use ReflectionClass;
use ReflectionException;
use Wakeapp\Bundle\SupervisorBundle\Annotation\Supervisor;
use Wakeapp\Bundle\SupervisorBundle\Dto\ConfigDto;
use function sprintf;

class SupervisorAnnotationService
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param Reader $reader
     * @param array $config
     */
    public function __construct(Reader $reader, array $config)
    {
        $this->reader = $reader;
        $this->config = $config;
    }

    /**
     * @param string $className
     *
     * @return Supervisor[]
     * @throws ReflectionException
     */
    public function getSupervisorAnnotationList(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);

        $annotationList = $this->reader->getClassAnnotations($reflectionClass);
        $annotationList = array_filter(
            $annotationList,
            static function ($annotation) {
                return $annotation instanceof Supervisor;
            }
        );

        return $annotationList;
    }


    /**
     * @param array $classNameList
     * @param array $options
     * @param string|null $server
     * @param string|null $environment
     *
     * @return string
     * @throws ReflectionException
     */
    public function export(array $classNameList, array $options, ?string $server, ?string $environment): string
    {
        $programList = $this->getProgramList($classNameList, $options, $server, $environment);

        if (empty($programList)) {
            return '';
        }

        $configuration = $this->buildConfiguration($programList);

        return $configuration->generate();
    }

    /**
     * @param array $config
     * @param array $options
     *
     * @return ConfigDto
     */
    protected function getConfigDto(array $config, array $options): ConfigDto
    {
        $exporter = $config['exporter'] ?? [];

        $configDto = new ConfigDto();

        $configDto->server = $exporter['server'] ?? null;
        $configDto->executor = $exporter['executor'] ?? null;
        $configDto->console = $exporter['console'] ?? null;
        $configDto->options = $options + ($exporter['program'] ?? []);

        return $configDto;
    }

    /**
     * @param array $classNameList
     * @param array $options
     * @param string|null $environment
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getProgramList(
        array $classNameList,
        array $options,
        ?string $server,
        ?string $environment
    ): array {
        $configDto = $this->getConfigDto($this->config, $options);

        $programList = [];

        foreach ($classNameList as $className) {
            $supervisorAnnotationList = $this->getSupervisorAnnotationList($className);

            if (empty($supervisorAnnotationList)) {
                continue;
            }

            foreach ($supervisorAnnotationList as $index => $annotation) {
                $isValid = $this->checkAnnotation($annotation, $server);

                if ($isValid === false) {
                    continue;
                }

                $instance = $index + 1;

                $programName = $this->buildProgramName($annotation, $instance);
                $command = $this->buildCommand($configDto, $annotation, $environment);
                $options = $configDto->options;

                $programList[] = [
                        'name' => $programName,
                        'command' => $command,
                        'numprocs' => $annotation->processes ?? 1,
                    ] + $options;
            }
        }

        return $programList;
    }

    /**
     * @param Supervisor $annotation
     * @param string|null $server
     *
     * @return bool
     */
    protected function checkAnnotation(Supervisor $annotation, ?string $server)
    {
        if (empty($server)) {
            throw new \RuntimeException('Option "--server" not found');
        }

        if (empty($annotation->server)) {
            throw new \RuntimeException('Annotation "server" not found');
        }

        $annotationServerList = explode(',', mb_strtolower($annotation->server));
        $annotationServerList = array_map('trim', $annotationServerList);

        return in_array(mb_strtolower($server), $annotationServerList);
    }

    /**
     * @param Supervisor $annotation
     * @param int $instance
     *
     * @return string
     */
    protected function buildProgramName(Supervisor $annotation, int $instance): string
    {
        $commandName = $this->getCommandName($annotation);
        $commandName = preg_replace('/[^\da-z]/i', '_', $commandName);

        if ($instance > 1) {
            $commandName .= '_' . $instance;
        }

        return $commandName;
    }

    /**
     * @param ConfigDto $configDto
     * @param Supervisor $annotation
     * @param string|null $environment
     *
     * @return string
     */
    protected function buildCommand(ConfigDto $configDto, Supervisor $annotation, ?string $environment): string
    {
        $executor = $this->getExecutor($configDto, $annotation);
        $console = $this->getConsole($configDto, $annotation);
        $commandName = $this->getCommandName($annotation);
        $params = $this->getParams($annotation);
        $environment = $this->getEnvironment($environment);

        $command = sprintf('%s %s %s %s %s', $executor, $console, $commandName, $params, $environment);

        return $command;
    }

    /**
     * @param ConfigDto $configDto
     * @param Supervisor $annotation
     *
     * @return string
     */
    protected function getExecutor(ConfigDto $configDto, Supervisor $annotation): string
    {
        return $annotation->executor ?? $configDto->executor ?? '';
    }

    /**
     * @param ConfigDto $configDto
     * @param Supervisor $annotation
     *
     * @return string
     */
    protected function getConsole(ConfigDto $configDto, Supervisor $annotation): string
    {
        return $annotation->console ?? $configDto->console ?? '';
    }

    /**
     * @param Supervisor $annotation
     *
     * @return string
     */
    protected function getCommandName(Supervisor $annotation): string
    {
        return $annotation->commandName ?? '';
    }

    /**
     * @param Supervisor $annotation
     *
     * @return string
     */
    protected function getParams(Supervisor $annotation): string
    {
        return $annotation->params ?? '';
    }

    /**
     * @param string|null $environment
     *
     * @return string
     */
    protected function getEnvironment(?string $environment): string
    {
        if ($environment) {
            return sprintf('--env=%s', $environment);
        }

        return '';
    }

    /**
     * @param array $programList
     *
     * @return Configuration
     */
    protected function buildConfiguration(array $programList): Configuration
    {
        $config = new Configuration;
        $config->registerProcessor(new CommandConfigurationProcessor);

        return array_reduce($programList, static function (Configuration $config, $program) {
            $command = new Command();

            //@todo create pull request fix syntax error (__contruct => __construct)
            $command->__contruct($checkKeys = false);

            foreach ($program as $k => $v) {
                $command->set($k, $v);
            }

            $config->add($command);

            return $config;
        }, $config);
    }
}
