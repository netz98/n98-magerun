<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use Installer\Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FromCommand extends AbstractCommand
{
    /**#@+
     * Command texts to output
     *
     * @var string
     */
    const COMMAND_NAME               = 'dev:module:dependencies:from';
    const COMMAND_DESCRIPTION        = 'Show list of modules which depend on %s module';
    const COMMAND_SECTION_TITLE_TEXT = "List of modules which depend on %s module";
    const COMMAND_NO_RESULTS_TEXT    = "No modules depend on %s module";
    /**#@-*/

    /**
     * Find dependencies of given module $moduleName.
     * If $recursive = true, dependencies will be collected recursively for all module dependencies
     *
     * @param string $moduleName
     * @param bool $recursive
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function findModuleDependencies($moduleName, $recursive = false)
    {
        if ($this->modules === null) {
            $this->modules = \Mage::app()->getConfig()->getNode('modules')->asArray();
        }

        if (isset($this->modules[$moduleName])) {
            $dependencies = array();
            foreach ($this->modules as $dependencyName => $module) {
                if (isset($module['depends'][$moduleName])) {
                    $dependencies[$dependencyName] = array(
                        $dependencyName,
                        isset($module['active']) ? $this->formatActive($module['active']) : '-',
                        isset($module['version']) ? $module['version'] : '-',
                        isset($module['codePool']) ? $module['codePool'] : '-',
                    );
                    if ($recursive) {
                        $dependencies = array_merge(
                            $dependencies,
                            $this->findModuleDependencies($dependencyName, $recursive)
                        );
                    }
                }
            }

            return $dependencies;
        } else {
            throw new \InvalidArgumentException(sprintf("Module %s was not found", $moduleName));
        }
    }
}
