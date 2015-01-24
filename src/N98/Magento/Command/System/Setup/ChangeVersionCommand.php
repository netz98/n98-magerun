<?php

namespace N98\Magento\Command\System\Setup;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

/**
 * Class ChangeVersionCommand
 * @package N98\Magento\Command\System\Setup
 */
class ChangeVersionCommand extends AbstractSetupCommand
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

            $moduleVersion  = $input->getArgument('version');
            $moduleName     = $this->getModule($input);
            $setupName      = $input->getArgument('setup');
            $moduleSetups   = $this->getModuleSetupResources($moduleName);

            if (empty($moduleSetups)) {
                $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));
                return;
            }

            if ($setupName === 'all') {
                foreach ($moduleSetups as $setupCode => $setup) {
                    $this->updateSetupResource($moduleName, $setupCode, $moduleVersion, $output);
                }
            } elseif (array_key_exists($setupName, $moduleSetups)) {
                $this->updateSetupResource($moduleName, $setupName, $moduleVersion, $output);
            } else {
                throw new \InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
            }
        }
    }

    /**
     * @param string $moduleName
     * @param string $setupResource
     * @param $version
     * @param OutputInterface $output
     * @return mixed
     */
    public function updateSetupResource($moduleName, $setupResource, $version, OutputInterface $output)
    {
        $resourceModel = $this->_getResourceSingleton('core/resource', 'Mage_Core_Model_Resource_Resource');

        $resourceModel->setDbVersion($setupResource, $version);
        $resourceModel->setDataVersion($setupResource, $version);

        $output->writeln(
            sprintf(
                '<info>Successfully updated: "%s" - "%s" to version: "%s"</info>',
                $moduleName,
                $setupResource,
                $version
            )
        );
    }
}
