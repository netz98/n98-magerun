<?php

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class AbstractSetupCommand
 * @package N98\Magento\Command\System\Setup
 */
class AbstractSetupCommand extends AbstractMagentoCommand
{
    /**
     * @param string $moduleName
     * @return array
     */
    public function getModuleSetupResources($moduleName)
    {
        $moduleSetups = array();
        $resources = \Mage::getConfig()->getNode('global/resources')->children();

        foreach ($resources as $resName => $resource) {
            $modName = (string) $resource->setup->module;

            if ($modName == $moduleName) {
                $moduleSetups[$resName] = $resource;
            }
        }

        return $moduleSetups;
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws InvalidArgumentException
     */
    public function getModule(InputInterface $input)
    {
        $modules = \Mage::app()->getConfig()->getNode('modules')->asArray();

        foreach ($modules as $moduleName => $data) {
            if (strtolower($moduleName) === strtolower($input->getArgument('module'))) {
                return $moduleName;
            }
        }

        throw new InvalidArgumentException(sprintf('No module found with name: "%s"', $input->getArgument('module')));
    }
}
