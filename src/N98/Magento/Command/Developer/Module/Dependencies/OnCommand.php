<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Dependencies;

use InvalidArgumentException;

/**
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
class OnCommand extends AbstractDependenciesCommand
{
    public const COMMAND_SECTION_TITLE_TEXT = 'List of module %s dependencies';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:dependencies:on';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Show list of modules which given module depends on.';

    /**
     * Find dependencies of given module $moduleName.
     * If $recursive = true, dependencies will be collected recursively for all module dependencies
     *
     * @inheritdoc
     */
    protected function findModuleDependencies(string $moduleName, bool $recursive = false): array
    {
        if (!isset($this->modules[$moduleName])) {
            throw new InvalidArgumentException(sprintf("Module %s was not found", $moduleName));
        }

        $dependencies = [];
        $module = $this->modules[$moduleName];
        if (isset($module['depends']) && is_array($module['depends']) && count($module['depends']) > 0) {
            foreach (array_keys($module['depends']) as $dependencyName) {
                if (isset($this->modules[$dependencyName])) {
                    $dependencies[] = [
                        $dependencyName,
                        isset($this->modules[$dependencyName]['active']) && is_string($this->modules[$dependencyName]['active'])
                            ? $this->formatActive($this->modules[$dependencyName]['active']) : '-',
                        $this->modules[$dependencyName]['version'] ?? '-',
                        $this->modules[$dependencyName]['codePool'] ?? '-'
                    ];
                    if ($recursive) {
                        $dependencies = array_merge(
                            $dependencies,
                            $this->findModuleDependencies($dependencyName, $recursive)
                        );
                    }
                } else {
                    $dependencies[] = [
                        $dependencyName,
                        'Not installed',
                        '-',
                        '-'
                    ];
                }
            }
        }

        return $dependencies;
    }
}
