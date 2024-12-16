<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Resource_Resource;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('sys:setup:change-version')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addArgument('version', InputArgument::REQUIRED, 'New version value')
            ->addArgument('setup', InputArgument::OPTIONAL, 'Setup code to update', 'all')
            ->setDescription('Change module setup resource version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $moduleVersion  = $input->getArgument('version');
        $moduleName     = $this->getModule($input);
        $setupName      = $input->getArgument('setup');
        $moduleSetups   = $this->getModuleSetupResources($moduleName);

        if ($moduleSetups === []) {
            $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));
            return Command::FAILURE;
        }

        if ($setupName === 'all') {
            foreach (array_keys($moduleSetups) as $setupCode) {
                $this->updateSetupResource($moduleName, $setupCode, $moduleVersion, $output);
            }
        } elseif (array_key_exists($setupName, $moduleSetups)) {
            $this->updateSetupResource($moduleName, $setupName, $moduleVersion, $output);
        } else {
            throw new InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
        }

        return Command::SUCCESS;
    }

    public function updateSetupResource(string $moduleName, string $setupResource, string $version, OutputInterface $output): void
    {
        /** @var Mage_Core_Model_Resource_Resource $mageCoreModelAbstract */
        $mageCoreModelAbstract = Mage::getResourceSingleton('core/resource');

        $mageCoreModelAbstract->setDbVersion($setupResource, $version);
        $mageCoreModelAbstract->setDataVersion($setupResource, $version);

        $output->writeln(
            sprintf(
                '<info>Successfully updated: "%s" - "%s" to version: "%s"</info>',
                $moduleName,
                $setupResource,
                $version,
            ),
        );
    }
}
