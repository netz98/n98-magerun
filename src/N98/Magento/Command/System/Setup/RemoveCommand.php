<?php

namespace N98\Magento\Command\System\Setup;

use InvalidArgumentException;
use Mage_Core_Model_Resource;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remove setup ommand
 *
 * @package N98\Magento\Command\System\Setup
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommand extends AbstractSetupCommand
{
    /**
     * Set up CLI options
     */
    protected function configure()
    {
        $this
            ->setName('sys:setup:remove')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addArgument('setup', InputArgument::OPTIONAL, 'Setup code to remove', 'all')
            ->setDescription('Remove module setup resource entry');
    }

    /**
     * @param InputInterface $input
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

        $moduleName = $this->getModule($input);
        $setupName = $input->getArgument('setup');
        $moduleSetups = $this->getModuleSetupResources($moduleName);

        if (empty($moduleSetups)) {
            $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));

            return 0;
        }

        if ($setupName === 'all') {
            foreach ($moduleSetups as $setupCode => $setup) {
                $this->removeSetupResource($moduleName, $setupCode, $output);
            }
        } elseif (array_key_exists($setupName, $moduleSetups)) {
            $this->removeSetupResource($moduleName, $setupName, $output);
        } else {
            throw new InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
        }
        return 0;
    }

    /**
     * @param string $moduleName
     * @param string $setupResource
     * @param OutputInterface $output
     */
    public function removeSetupResource($moduleName, $setupResource, OutputInterface $output)
    {
        /** @var Mage_Core_Model_Resource $model */
        $model = $this->_getModel('core/resource', 'Mage_Core_Model_Resource');
        $writeAdapter = $model->getConnection('core_write');
        if (!$writeAdapter) {
            throw new RuntimeException('Database not configured');
        }
        $table = $model->getTableName('core_resource');

        if ($writeAdapter->delete($table, ['code = ?' => $setupResource]) > 0) {
            $output->writeln(
                sprintf(
                    '<info>Successfully removed setup resource: "%s" from module: "%s" </info>',
                    $setupResource,
                    $moduleName
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    '<error>No entry was found for setup resource: "%s" in module: "%s" </error>',
                    $setupResource,
                    $moduleName
                )
            );
        }
    }
}
