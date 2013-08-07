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

            $config->deleteConfig(
                $input->getArgument('path'),
                $input->getOption('scope'),
                $scopeId
            );
            $output->writeln('<info>Deleted entry</info> <comment>scope => ' . $input->getOption('scope') . ' path => ' . $input->getArgument('path') . '</comment></info>');
        }
    }
}