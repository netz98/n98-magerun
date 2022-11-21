<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use InvalidArgumentException;
use Mage;

class FromCommand extends AbstractCommand
{
    /**#@+
     * Command texts to output
     *
     * @var string
     */
    public const COMMAND_NAME = 'dev:module:dependencies:from';
    public const COMMAND_DESCRIPTION = 'Show list of modules which depend on %s module';
    public const COMMAND_SECTION_TITLE_TEXT = "List of modules which depend on %s module";
    public const COMMAND_NO_RESULTS_TEXT = "No modules depend on %s module";
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function findModuleDependencies($moduleName, $recursive = false)
    {
        if ($this->modules === null) {
            $this->modules = Mage::app()->getConfig()->getNode('modules')->asArray();
        }

        if (!isset($this->modules[$moduleName])) {
            throw new InvalidArgumentException(sprintf("Module %s was not found", $moduleName));
        }

        $dependencies = [];
        foreach ($this->modules as $dependencyName => $module) {
            if (!isset($module['depends'][$moduleName])) {
                continue;
            }

            $dependencies[$dependencyName] = [$dependencyName, isset($module['active']) ? $this->formatActive($module['active']) : '-', $module['version'] ?? '-', $module['codePool'] ?? '-'];

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
