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

        $help = <<<HELP
Set a store config value by path.
To set a value of a specify store view you must set the "scope" and "scope-id" option.

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

            $value = str_replace(array('\n', '\r'), array("\n", "\r"), $input->getArgument('value'));
            $value = $this->_formatValue($value, ($input->getOption('encrypt') ? 'encrypt' : false));

            $config->saveConfig(
                $input->getArgument('path'),
                $value,
                $input->getOption('scope'),
                $scopeId
            );
            $output->writeln('<comment>' . $input->getArgument('path') . "</comment> => <comment>" . $input->getArgument('value') . '</comment>');
        }
    }
}
