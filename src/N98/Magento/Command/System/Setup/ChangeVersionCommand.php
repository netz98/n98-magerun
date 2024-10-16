<?php

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Change setup version command
 *
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            return 0;
        }

        $moduleVersion = $input->getArgument('version');
        $moduleName = $this->getModule($input);
        $setupName = $input->getArgument('setup');
        $moduleSetups = $this->getModuleSetupResources($moduleName);

        if (empty($moduleSetups)) {
            $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));
            return 0;
        }

        if ($setupName === 'all') {
            foreach ($moduleSetups as $setupCode => $setup) {
                $this->updateSetupResource($moduleName, $setupCode, $moduleVersion, $output);
            }
        } elseif (array_key_exists($setupName, $moduleSetups)) {
            $this->updateSetupResource($moduleName, $setupName, $moduleVersion, $output);
        } else {
            throw new InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
        }
        return 0;
    }

    /**
     * @param string $moduleName
     * @param string $setupResource
     * @param $version
     * @param OutputInterface $output
     */
    public function updateSetupResource($moduleName, $setupResource, $version, OutputInterface $output)
    {
        $resourceModel = $this->_getResourceSingleton('core/resource');

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
