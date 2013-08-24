<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use Installer\Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OnCommand extends AbstractCommand
{
    /**#@+
     * Command texts to output
     *
     * @var string
     */
    const COMMAND_NAME               = 'dev:module:dependencies:on';
    const COMMAND_DESCRIPTION        = 'Show list of modules which given module depends on';
    const COMMAND_SECTION_TITLE_TEXT = "List of module %s dependencies";
    const COMMAND_NO_RESULTS_TEXT    = "Module %s doesn't have dependencies";
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
            $module       = $this->modules[$moduleName];
            if (isset($module['depends']) && is_array($module['depends']) && count($module['depends']) > 0) {
                foreach (array_keys($module['depends']) as $dependencyName) {
                    if (isset($this->modules[$dependencyName])) {
                        $dependencies[$dependencyName] = array(
                            $dependencyName,
                            isset($this->modules[$dependencyName]['active'])
                                ? $this->formatActive($this->modules[$dependencyName]['active'])
                                : '-',
                            isset($this->modules[$dependencyName]['version'])
                                ? $this->modules[$dependencyName]['version']
                                : '-',
                            isset($this->modules[$dependencyName]['codePool'])
                                ? $this->modules[$dependencyName]['codePool']
                                : '-',
                        );
                        if ($recursive) {
                            $dependencies = array_merge(
                                $dependencies,
                                $this->findModuleDependencies($dependencyName, $recursive)
                            );
                        }
                    } else {
                        $dependencies[$dependencyName] = array($dependencyName, 'Not installed', '-', '-');
                    }
                }
            }

            return $dependencies;
        } else {
            throw new \InvalidArgumentException(sprintf("Module %s was not found", $moduleName));
        }
    }
}
