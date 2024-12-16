<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set config command
 *
 * @package N98\Magento\Command\Config
 */
class SetCommand extends AbstractConfigCommand
{
    protected function configure(): void
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addArgument('value', InputArgument::REQUIRED, 'The config value')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_OPTIONAL,
                "The config value's scope (default, websites, stores)",
                'default',
            )
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, "The config value's scope ID", '0')
            ->addOption(
                'encrypt',
                null,
                InputOption::VALUE_NONE,
                "The config value should be encrypted using local.xml's crypt key",
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                "Allow creation of non-standard scope-id's for websites and stores",
            )
            ->addOption(
                'no-null',
                null,
                InputOption::VALUE_NONE,
                'Do not treat value NULL as ' . self::DISPLAY_NULL_UNKNOWN_VALUE . ' value',
            )
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Set a store config value by path.
To set a value of a specify store view you must set the "scope" and "scope-id" option.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $mageCoreModelConfig = $this->_getConfigModel();
        if (!$mageCoreModelConfig->getResourceModel()) {
            // without a resource model, a config option can't be saved.
            return Command::FAILURE;
        }

        $allowZeroScope = $input->getOption('force');
        $scope          = $input->getOption('scope');
        $this->_validateScopeParam($scope);
        $scopeId        = (int) $this->_convertScopeIdParam($scope, (string) $input->getOption('scope-id'), $allowZeroScope);
        $valueDisplay   = $input->getArgument('value');
        $value          = $valueDisplay;

        if ($value === 'NULL' && !$input->getOption('no-null')) {
            if ($input->getOption('encrypt')) {
                throw new InvalidArgumentException('Encryption is not possbile for NULL values');
            }

            $value = null;
            $valueDisplay = self::DISPLAY_NULL_UNKNOWN_VALUE;
        } else {
            $value = str_replace(['\n', '\r'], ["\n", "\r"], $value);
            $value = $this->_formatValue($value, ($input->getOption('encrypt') ? 'encrypt' : false));
        }

        $mageCoreModelConfig->saveConfig(
            $input->getArgument('path'),
            $value,
            $scope,
            $scopeId,
        );

        $output->writeln(
            '<comment>' . $input->getArgument('path') . '</comment> => <comment>' . $valueDisplay .
            '</comment>',
        );

        return Command::SUCCESS;
    }
}
