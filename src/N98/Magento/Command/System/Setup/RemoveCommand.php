<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Resource;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remove setup command
 *
 * @package N98\Magento\Command\System\Setup
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommand extends AbstractSetupCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:setup:remove')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addArgument('setup', InputArgument::OPTIONAL, 'Setup code to remove', 'all')
            ->setDescription('Remove module setup resource entry');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $moduleName = $this->getModule($input);
        $setupName = $input->getArgument('setup');
        $moduleSetups = $this->getModuleSetupResources($moduleName);

        if ($moduleSetups === []) {
            $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));
            return Command::FAILURE;
        }

        if ($setupName === 'all') {
            foreach (array_keys($moduleSetups) as $setupCode) {
                $this->removeSetupResource($moduleName, $setupCode, $output);
            }
        } elseif (array_key_exists($setupName, $moduleSetups)) {
            $this->removeSetupResource($moduleName, $setupName, $output);
        } else {
            throw new InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
        }

        return Command::SUCCESS;
    }

    public function removeSetupResource(string $moduleName, string $setupResource, OutputInterface $output): void
    {
        $mageCoreModelResource = $this->getMageCoreResource();
        $writeAdapter = $mageCoreModelResource->getConnection('core_write');
        if (!$writeAdapter) {
            throw new RuntimeException('Database not configured');
        }

        $table = $mageCoreModelResource->getTableName('core_resource');

        if ($writeAdapter->delete($table, ['code = ?' => $setupResource]) > 0) {
            $output->writeln(
                sprintf(
                    '<info>Successfully removed setup resource: "%s" from module: "%s" </info>',
                    $setupResource,
                    $moduleName,
                ),
            );
        } else {
            $output->writeln(
                sprintf(
                    '<error>No entry was found for setup resource: "%s" in module: "%s" </error>',
                    $setupResource,
                    $moduleName,
                ),
            );
        }
    }

    public function getMageCoreResource(): Mage_Core_Model_Resource
    {
        /** @var Mage_Core_Model_Resource $model */
        $model = Mage::getModel('core/resource');
        return $model;
    }
}
