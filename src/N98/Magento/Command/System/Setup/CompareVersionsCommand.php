<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareVersionsCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:compare-versions')
            ->addOption('ignore-data', null, InputOption::VALUE_NONE, 'Ignore data updates')
            ->setDescription('Compare module version with core_resource table.');
        $help = <<<HELP
Compares module version with saved setup version in `core_resource` table and displays version mismatch.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $modules = \Mage::getConfig()->getNode('modules');
            $resourceModel = $this->_getResourceSingleton('core/resource', 'Mage_Core_Model_Resource_Resource');
            $setups = \Mage::getConfig()->getNode('global/resources')->children();
            $ignoreDataUpdate = $input->getOption('ignore-data');
            if (!$ignoreDataUpdate) {
                $columnWidths = array('columnWidths' => array(40, 10, 10, 10, 6));
                $table = new \Zend_Text_Table($columnWidths);
                $table->appendRow(
                    array(
                         'Setup',
                         'Module',
                         'DB',
                         'Data',
                         'Status'
                    )
                );
            } else {
                $columnWidths = array('columnWidths' => array(40, 10, 10, 6));
                $table = new \Zend_Text_Table($columnWidths);
                $table->appendRow(
                    array(
                         'Setup',
                         'Module',
                         'DB',
                         'Status'
                    )
                );
            }
            $errorCounter = 0;
            foreach ($setups as $setupName => $setup) {
                $moduleName = (string) $setup->setup->module;
                $moduleVersion = (string) $modules->{$moduleName}->version;
                $dbVersion = (string) $resourceModel->getDbVersion($setupName);
                if (!$ignoreDataUpdate) {
                    $dataVersion = (string) $resourceModel->getDataVersion($setupName);
                }
                $ok = $dbVersion == $moduleVersion;
                if ($ok && !$ignoreDataUpdate) {
                    $ok = $dataVersion == $moduleVersion;
                }
                if (!$ok) {
                    $errorCounter++;
                }

                $row = array();
                $row['Setup'] = $setupName;
                $row['Version'] = $moduleVersion;
                $row['DB-Version'] = $dbVersion;
                if (!$ignoreDataUpdate) {
                    $row['Data-Version'] = $dataVersion;
                }
                $row['Status'] = $ok ? 'OK' : 'Error';

                $table->appendRow($row);
            }

            $output->write($table->render());

            if ($errorCounter > 0) {
                $output->writeln('<error>' . $errorCounter . ' error' . ($errorCounter > 1 ? 's' : '') . ' was found!</error>');
            } else {
                $this->writeSection($output, 'No setup problems was found.', 'info');
            }
        }
    }
}
