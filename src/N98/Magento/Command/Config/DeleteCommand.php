<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends AbstractConfigCommand
{
    /**
     * @var array
     */
    protected $_scopes = array(
        'default',
        'websites',
        'stores',
    );

    protected function configure()
    {
        $this
            ->setName('config:delete')
            ->setDescription('Deletes a store config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope (default, websites, stores)', 'default')
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID', '0')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all entries by path')
        ;

        $help = <<<HELP
To delete all entries if a path you can set the option --all.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $config = $this->_getConfigModel();

            $this->_validateScopeParam($input->getOption('scope'));
            $scopeId = $this->_convertScopeIdParam($input->getOption('scope'), $input->getOption('scope-id'));

            $deleted = array();

            $path = $input->getArgument('path');
            $pathArray = array();
            if (strstr($path, '*')) {
                /* @var $collection \Mage_Core_Model_Resource_Db_Collection_Abstract */
                $collection = $this->_getConfigDataModel()->getCollection();

                $searchPath = str_replace('*', '%', $path);
                $collection->addFieldToFilter('path', array('like' => $searchPath));

                if ($scopeId = $input->getOption('scope')) {
                    $collection->addFieldToFilter('scope', array('eq' => $scopeId));
                }
                $collection->addOrder('path', 'ASC');

                foreach ($collection as $item) {
                    $pathArray[] = $item->getPath();
                }
            } else {
                $pathArray[] = $path;
            }

            foreach ($pathArray as $pathToDelete) {
                $deleted = array_merge($deleted, $this->_deletePath($input, $config, $pathToDelete, $scopeId));
            }
        }

        if (count($deleted) > 0) {
            $this->getHelper('table')
                ->setHeaders(array('deleted path', 'scope', 'id'))
                ->setRows($deleted)
                ->render($output);
        }
    }

    /**
     * @param InputInterface $input
     * @param                $config
     * @param                $path
     * @param                $scopeId
     *
     * @return array
     */
    protected function _deletePath(InputInterface $input, $config, $path, $scopeId)
    {
        $deleted = array();
        if ($input->getOption('all')) {

            // Default
            $config->deleteConfig(
                $path,
                'default',
                0
            );

            $deleted[] = array(
                'path'    => $path,
                'scope'   => 'default',
                'scopeId' => 0,
            );

            // Delete websites
            foreach (\Mage::app()->getWebsites() as $website) {
                $config->deleteConfig(
                    $path,
                    'websites',
                    $website->getId()
                );
                $deleted[] = array(
                    'path'    => $path,
                    'scope'   => 'websites',
                    'scopeId' => $website->getId(),
                );
            }

            // Delete stores
            foreach (\Mage::app()->getStores() as $store) {
                $config->deleteConfig(
                    $path,
                    'stores',
                    $store->getId()
                );
                $deleted[] = array(
                    'path'    => $path,
                    'scope'   => 'stores',
                    'scopeId' => $store->getId(),
                );
            }

        } else {
            $config->deleteConfig(
                $path,
                $input->getOption('scope'),
                $scopeId
            );

            $deleted[] = array(
                'path'    => $path,
                'scope'   => $input->getOption('scope'),
                'scopeId' => $scopeId,
            );

        }

        return $deleted;
    }
}
