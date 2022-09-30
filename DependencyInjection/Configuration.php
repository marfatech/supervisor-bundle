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

namespace Marfatech\Bundle\SupervisorBundle\DependencyInjection;

use Closure;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use function is_dir;
use function sprintf;

class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('marfatech_supervisor');

        $rootNode
            ->children()
                ->arrayNode('exporter')
                    ->children()
                        ->variableNode('program')->end()
                        ->scalarNode('executor')->example('php')->end()
                        ->scalarNode('console')->example('app/console')->end()
                    ->end()
                ->end()
                ->arrayNode('source_directories')
                    ->defaultValue(['src'])
                    ->prototype('scalar')->end()
                    ->validate()
                        ->always($this->validationForSourceDirectoryList())
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @return Closure
     */
    protected function validationForSourceDirectoryList(): Closure
    {
        $projectDir = $this->projectDir;

        return static function (?array $directoryList) use ($projectDir) {
            foreach ($directoryList as $directory) {
                if (!is_dir($projectDir . DIRECTORY_SEPARATOR . $directory)) {
                    throw new InvalidConfigurationException(sprintf(
                        'Received directory "%s" under "source_directories" does not exists',
                        $directory
                    ));
                }
            }

            return $directoryList;
        };
    }
}
