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
            ->setDescription('Deletes a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope (default, websites, stores)', 'default')
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID', '0')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all entries by path')
        ;
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
            $config = $this->_getConfigModel();

            $this->_validateScopeParam($input->getOption('scope'));
            $scopeId = $this->_convertScopeIdParam($input->getOption('scope'), $input->getOption('scope-id'));

            $deleted = array();

            if ($input->getOption('all')) {

                // Default
                $config->deleteConfig(
                    $input->getArgument('path'),
                    'default',
                    0
                );

                $deleted[] = array(
                    'path'    => $input->getArgument('path'),
                    'scope'   => 'default',
                    'scopeId' => 0,
                );

                // Delete websites
                foreach (\Mage::app()->getWebsites() as $website) {
                    $config->deleteConfig(
                        $input->getArgument('path'),
                        'websites',
                        $website->getId()
                    );
                    $deleted[] = array(
                        'path'    => $input->getArgument('path'),
                        'scope'   => 'websites',
                        'scopeId' => $website->getId(),
                    );
                }

                // Delete stores
                foreach (\Mage::app()->getStores() as $store) {
                    $config->deleteConfig(
                        $input->getArgument('path'),
                        'stores',
                        $store->getId()
                    );
                    $deleted[] = array(
                        'path'    => $input->getArgument('path'),
                        'scope'   => 'stores',
                        'scopeId' => $store->getId(),
                    );
                }
            } else {
                $config->deleteConfig(
                    $input->getArgument('path'),
                    $input->getOption('scope'),
                    $scopeId
                );

                $deleted[] = array(
                    'path'    => $input->getArgument('path'),
                    'scope'   => $input->getOption('scope'),
                    'scopeId' => $scopeId,
                );
            }
        }

        if (count($deleted) > 0) {
            $this->getHelper('table')
                ->setHeaders(array('deleted path', 'scope', 'id'))
                ->setRows($deleted)
                ->render($output);
        }
    }
}