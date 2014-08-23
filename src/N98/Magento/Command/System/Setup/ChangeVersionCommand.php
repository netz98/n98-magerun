<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

/**
 * Class ChangeVersionCommand
 * @package N98\Magento\Command\System\Setup
 */
class ChangeVersionCommand extends AbstractMagentoCommand
{
    /**
     * Set up CLI options
     */
    protected function configure()
    {
        $this
            ->setName('sys:setup:change-version')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addArgument('version', InputArgument::REQUIRED, 'New version value')
            ->addArgument('setup', InputArgument::OPTIONAL, 'Setup code to update', 'all')
            ->setDescription('Change module setup resource version');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if ($this->initMagento()) {
            $modules = $modules = \Mage::app()->getConfig()->getNode('modules')->asArray();
            $moduleName = $input->getArgument('module');
            $moduleVersion = $input->getArgument('version');

            if (isset($modules[$moduleName])) {
                // Get setups
                $moduleSetups   = array();
                $resources      = \Mage::getConfig()->getNode('global/resources')->children();
                $setupName      = $input->getArgument('setup');
                $resourceModel = $this->_getResourceSingleton('core/resource', 'Mage_Core_Model_Resource_Resource');

                foreach ($resources as $resName => $resource) {
                    $modName = (string) $resource->setup->module;

                    if ($modName == $moduleName) {
                        $moduleSetups[$resName] = $resource;
                    }
                }

                if ($setupName === 'all') {
                    foreach ($moduleSetups as $setupCode => $setup) {
                        $resourceModel->setDbVersion($setupCode, $moduleVersion);
                        $resourceModel->setDataVersion($setupCode, $moduleVersion);

                        $output->writeln(
                            sprintf(
                                '<info>Successfully updated %s - %s to version %s</info>',
                                $moduleName,
                                $setupCode,
                                $moduleVersion
                            )
                        );
                    }
                } else if (array_key_exists($setupName, $moduleSetups)) {
                    $resourceModel->setDbVersion($setupName, $moduleVersion);
                    $resourceModel->setDataVersion($setupName, $moduleVersion);

                    $output->writeln(
                        sprintf(
                            '<info>Successfully updated %s - %s to version %s</info>',
                            $moduleName,
                            $setupName,
                            $moduleVersion
                        )
                    );
                } else {
                    $output->writeln('<error>Error no setup found with the name ' . $setupName . '</error>');
                }
            } else {
                $output->writeln('<error>No module found!</error>');
            }
        }
    }
}
