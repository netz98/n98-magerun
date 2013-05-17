<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a core config item')
            ->setHelp(<<<EOT
If <info>path</info> is not set, all available config items will be listed.
The <info>path</info> may contain wildcards (*).
If <info>path</info> ends with a trailing slash, all child items will be listed. E.g.

    config:get web/ 
is the same as
    config:get web/*
EOT
                )
            ->addArgument('path', InputArgument::OPTIONAL, 'The config path')
            ->addOption('scope-id', null, InputOption::VALUE_REQUIRED, 'The config value\'s scope ID')
            ->addOption('decrypt', null, InputOption::VALUE_NONE, 'Decrypt the config value using local.xml\'s crypt key')
        ;
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getConfigDataModel()
    {
        return $this->_getModel('core/config_data', 'Mage_Core_Model_Config_Data');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            /* @var $collection \Mage_Core_Model_Resource_Db_Collection_Abstract */
            $collection = $this->_getConfigDataModel()->getCollection();
            $searchPath = $input->getArgument('path');

            if(substr($input->getArgument('path'), -1, 1) === '/') {
                $searchPath .= '*';
            }

            $collection->addFieldToFilter('path', array(
                'like' => str_replace('*', '%', $searchPath)
            ));
            
            if ($scopeId = $input->getOption('scope-id')) {
                $collection->addFieldToFilter('scope_id', array(
                    'eq' => $scopeId
                ));
            }

            $collection->addOrder('path', 'ASC');

            // sort according to the config overwrite order
            // trick to force order default -> (f)website -> store , because f comes after d and before s
            $collection->addOrder('REPLACE(scope, "website", "fwebsite")', 'ASC');

            $collection->addOrder('scope_id', 'ASC');

            if($collection->count() == 0) {
                $output->writeln(sprintf("Couldn't find a config value for \"%s\"", $input->getArgument('path')));
                return;
            }

            foreach ($collection as $item) {
                $table[] = array(
                    'Path'     => $item->getPath(),
                    'Scope'    => str_pad($item->getScope(), 8, ' ', STR_PAD_BOTH),
                    'Scope-ID' => str_pad($item->getScopeId(), 8, ' ', STR_PAD_BOTH),
                    'Value'    => substr($this->_formatValue($item->getValue(), $input->getOption('decrypt')), 0, 50)
                );
            }

            ksort($table);
            $this->getHelper('table')->write($output, $table);
        }
    }

    /**
     * @param string $value
     * @param boolean $decryptionRequired
     * @return string
     */
    protected function _formatValue($value, $decryptionRequired)
    {
        if ($decryptionRequired) {
            $value = $this->getEncryptionModel()->decrypt($value);
        }

        return $value;
    }
}