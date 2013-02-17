<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addArgument('value', InputArgument::REQUIRED, 'The config value')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope', 'default')
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
            $config->saveConfig(
                $input->getArgument('path'),
                $this->_formatValue($input->getArgument('value'), $input->getOption('encrypt')),
                $input->getOption('scope'),
                $input->getOption('scope-id')
            );
            $output->writeln('<comment>' . $input->getArgument('path') . "</comment> => <comment>" . $input->getArgument('value') . '</comment>');
        }
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