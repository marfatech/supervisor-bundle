<?php

declare(strict_types=1);

/*
 * This file is part of the SupervisorBundle package.
 *
 * (c) Marfatech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Marfatech\Bundle\SupervisorBundle\Service;

use Psr\Log\LoggerAwareTrait;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class SupervisorSourceService
{
    use LoggerAwareTrait;

    /**
     * @var SupervisorAnnotationService
     */
    protected $supervisorAnnotationService;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var array
     */
    protected $sourceList = [];

    /**
     * @param SupervisorAnnotationService $supervisorAnnotationService
     * @param string $projectDir
     * @param array $config
     */
    public function __construct(
        SupervisorAnnotationService $supervisorAnnotationService,
        string $projectDir,
        array $config = []
    ) {
        $this->supervisorAnnotationService = $supervisorAnnotationService;
        $this->projectDir = $projectDir;
        $this->sourceList = $config['source_directories'] ?? [];
    }

    /**
     * @param Finder $finder
     *
     * @return array
     * @throws ReflectionException
     */
    public function getClassNameList(Finder $finder): array
    {
        $sourceClassList = [];

        $declaredClassList = $this->getDeclaredClasses($finder);

        foreach ($declaredClassList as $className) {
            try {
                $annotationList = $this->supervisorAnnotationService->getSupervisorAnnotationList($className);
            } catch (\Exception $exception) {
                if ($this->logger) {
                    $this->logger->warning($exception->getMessage());
                }
                continue;
            }

            if (empty($annotationList)) {
                continue;
            }

            $sourceClassList[] = $className;
        }

        return array_unique($sourceClassList);
    }

    /**
     * @return Finder
     */
    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->files()->name('*.php');

        foreach ($this->sourceList as $directoryOrFile) {
            $finder->in($this->projectDir . DIRECTORY_SEPARATOR . $directoryOrFile);
        }

        return $finder;
    }

    /**
     * @param Finder $finder
     *
     * @return array
     */
    protected function getDeclaredClasses(Finder $finder): array
    {
        foreach ($finder as $splFileInfo) {
            include_once($splFileInfo->getPathname());
        }

        return get_declared_classes();
    }
}
