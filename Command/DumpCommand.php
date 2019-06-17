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

namespace Wakeapp\Bundle\SupervisorBundle\Command;

use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wakeapp\Bundle\SupervisorBundle\Cache\SupervisorCache;
use Wakeapp\Bundle\SupervisorBundle\Service\SupervisorAnnotationService;
use Wakeapp\Bundle\SupervisorBundle\Service\SupervisorSourceService;

class DumpCommand extends Command
{
    /**
     * @var SupervisorAnnotationService
     */
    protected $supervisorAnnotationService;

    /**
     * @var SupervisorSourceService
     */
    protected $supervisorSourceService;

    /**
     * @required
     *
     * @param SupervisorAnnotationService $supervisorAnnotationService
     * @param SupervisorSourceService $supervisorSourceService
     */
    public function injectDependency(
        SupervisorAnnotationService $supervisorAnnotationService,
        SupervisorSourceService $supervisorSourceService
    ): void {
        $this->supervisorAnnotationService = $supervisorAnnotationService;
        $this->supervisorSourceService = $supervisorSourceService;
    }

    protected function configure(): void
    {
        $this
            ->setName('wakeapp:supervisor:dump')
            ->setDescription('Dump the supervisor configuration')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'The desired user to invoke the command as')
            ->addOption('server', null, InputOption::VALUE_OPTIONAL, 'Only include programs for the specified server')
            ->addOption(
                'options',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Set supervisor config options'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = $input->getOption('server');
        $environment = $input->getOption('env');
        $options = (array)$input->getOption('options');

        $finder = $this->supervisorSourceService->getFinder();
        $classNameList = $this->supervisorSourceService->getClassNameList($finder);

        $configuration = $this->supervisorAnnotationService->export($classNameList, $options, $server, $environment);

        $output->writeln($configuration);
    }
}
