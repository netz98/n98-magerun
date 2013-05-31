<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractConfigCommand
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
            ->setName('config:set')
            ->setDescription('Set a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addArgument('value', InputArgument::REQUIRED, 'The config value')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope (default, websites, stores)', 'default')
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID', '0')
            ->addOption('encrypt', null, InputOption::VALUE_NONE, 'The config value should be encrypted using local.xml\'s crypt key')
        ;
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getConfigModel()
    {
        return $this->_getModel('core/config','Mage_Core_Model_Config');
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

            $config->saveConfig(
                $input->getArgument('path'),
                $this->_formatValue($input->getArgument('value'), $input->getOption('encrypt')),
                $input->getOption('scope'),
                $scopeId
            );
            $output->writeln('<comment>' . $input->getArgument('path') . "</comment> => <comment>" . $input->getArgument('value') . '</comment>');
        }
    }

    /**
     * @param string $scope
     */
    protected function _validateScopeParam($scope)
    {
        if (!in_array($scope, $this->_scopes)) {
            throw new \InvalidArgumentException(
                'Invalid scope parameter. It must be one of ' . implode(',', $this->_scopes)
            );
        }
    }

    /**
     * @param string $scope
     * @param string $scopeId
     *
     * @return string
     */
    protected function _convertScopeIdParam($scope, $scopeId)
    {
        if ($scope == 'websites' && !is_numeric($scopeId)) {
            $website = \Mage::app()->getWebsite($scopeId);
            if (!$website) {
                throw new \InvalidArgumentException('Invalid scope parameter. Website does not exist.');
            }

            return $website->getId();
        }

        if ($scope == 'stores' && !is_numeric($scopeId)) {
            $store = \Mage::app()->getStore($scopeId);
            if (!$store) {
                throw new \InvalidArgumentException('Invalid scope parameter. Store does not exist.');
            }

            return $store->getId();
        }

        return $scopeId;
    }

    /**
     * @param string $value
     * @param boolean $encryptionRequired
     * @return string
     */
    protected function _formatValue($value, $encryptionRequired)
    {
        if ($encryptionRequired) {
            $value = $this->getEncryptionModel()->encrypt($value);
        }

        return $value;
    }
}