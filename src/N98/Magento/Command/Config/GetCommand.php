<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractMagentoCommand
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
        ;
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getConfigDataModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Core_Model_Config_Data');
        } else {
            return \Mage::getModel('core/config_data');
        }
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

            if($collection->count() == 0) {
                $output->writeln(sprintf("Couldn't find a config value for \"%s\"", $input->getArgument('path')));
                return;
            }

            foreach ($collection as $item){
                $table[$item->getPath()] = array(
                    'Path'     => $item->getPath(),
                    'Scope'    => str_pad($item->getScope(), 8, ' ', STR_PAD_BOTH),
                    'Scope-ID' => str_pad($item->getScopeId(), 8, ' ', STR_PAD_BOTH),
                    'Value'    => substr($item->getValue(), 0, 50)
                );
            }

            ksort($table);
            $this->getHelper('table')->write($output, $table);
        }
    }
}