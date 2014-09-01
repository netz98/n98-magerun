<?php

namespace N98\Magento\Command\System\Setup;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

/**
 * Class RemoveCommand
 * @package N98\Magento\Command\System\Setup
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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if ($this->initMagento()) {
            $moduleName     = $this->getModule($input);
            $setupName      = $input->getArgument('setup');
            $moduleSetups   = $this->getModuleSetupResources($moduleName);

            if (empty($moduleSetups)) {
                $output->writeln(sprintf('No setup resources found for module: "%s"', $moduleName));
                return;
            }

            if ($setupName === 'all') {
                foreach ($moduleSetups as $setupCode => $setup) {
                    $this->removeSetupResource($moduleName, $setupCode, $output);
                }
            } elseif (array_key_exists($setupName, $moduleSetups)) {
                $this->removeSetupResource($moduleName, $setupName, $output);
            } else {
                throw new \InvalidArgumentException(sprintf('Error no setup found with the name: "%s"', $setupName));
            }
        }
    }

    /**
     * @param string $moduleName
     * @param string $setupResource
     * @param OutputInterface $output
     * @return mixed
     */
    public function removeSetupResource($moduleName, $setupResource, OutputInterface $output)
    {
        $model          = $this->_getModel('core/resource', 'Mage_Core_Model_Resource');
        $table          = $model->getTableName('core_resource');
        $writeAdapter   = $model->getConnection('core_write');

        if ($writeAdapter->delete($table, array('code = ?' => $setupResource)) > 0) {
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
