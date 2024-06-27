<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Dependencies;

use InvalidArgumentException;

/**
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
class FromCommand extends AbstractDependenciesCommand
{
    public const COMMAND_SECTION_TITLE_TEXT = 'List of modules which depend on %s module';

    public const COMMAND_NO_RESULTS_TEXT = "No modules depend on %s module";

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:dependencies:from';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Show list of modules which depend on %s module.';

    /**
     * @inheritdoc
     */
    protected function findModuleDependencies($moduleName, $recursive = false): array
    {
        if (!isset($this->modules[$moduleName])) {
            throw new InvalidArgumentException(sprintf('Module %s was not found', $moduleName));
        }

        $dependencies = [];
        foreach ($this->modules as $dependencyName => $module) {
            if (!isset($module['depends'][$moduleName])) {
                continue;
            }

            $dependencies[$dependencyName] = [
                $dependencyName,
                isset($module['active']) && is_string($module['active']) ? $this->formatActive($module['active']) : '-',
                $module['version'] ?? '-',
                $module['codePool'] ?? '-'
            ];

            if ($recursive) {
                $dependencies = array_merge(
                    $dependencies,
                    $this->findModuleDependencies($dependencyName, $recursive)
                );
            }
        }

        return $dependencies;
    }
}
