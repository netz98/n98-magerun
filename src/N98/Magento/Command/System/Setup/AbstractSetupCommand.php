<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Config;
use Mage_Core_Model_Config_Element;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class AbstractSetupCommand
 *
 * @package N98\Magento\Command\System\Setup
 */
class AbstractSetupCommand extends AbstractMagentoCommand
{
    public function getModuleSetupResources(string $moduleName): array
    {
        $moduleSetups = [];

        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getConfig();
        /** @var Mage_Core_Model_Config_Element $resources */
        $resources = $config->getNode('global/resources');
        foreach ($resources->children() as $resName => $resource) {
            $modName = (string) $resource->setup->module;
            if ($modName === $moduleName) {
                $moduleSetups[$resName] = $resource;
            }
        }

        return $moduleSetups;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getModule(InputInterface $input): string
    {
        $config = Mage::app()->getConfig();

        $modules = $config->getNode('modules');
        if ($modules) {
            foreach ($modules->asArray() as $moduleName => $data) {
                if (strtolower($moduleName) === strtolower($input->getArgument('module'))) {
                    return $moduleName;
                }
            }
        }

        throw new InvalidArgumentException(sprintf('No module found with name: "%s"', $input->getArgument('module')));
    }
}
