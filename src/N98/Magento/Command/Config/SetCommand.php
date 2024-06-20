<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use Mage_Core_Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Config set command
 *
 * @package N98\Magento\Command\Config
 */
class SetCommand extends AbstractConfigCommand
{
    public const COMMAND_ARGUMENT_PATH = 'path';

    public const COMMAND_ARGUMENT_VALUE = 'value';

    public const COMMAND_OPTION_SCOPE = 'scope';

    public const COMMAND_OPTION_SCOPE_ID = 'scope-id';

    public const COMMAND_OPTION_ENCRYPT = 'encrypt';

    public const COMMAND_OPTION_FORCE = 'force';

    public const COMMAND_OPTION_NO_NULL = 'no-null';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'config:set';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Set a core config item.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_PATH,
                InputArgument::REQUIRED,
                'The config path'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_VALUE,
                InputArgument::REQUIRED,
                'The config value'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope (default, websites, stores)',
                'default'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope ID',
                '0'
            )
            ->addOption(
                self::COMMAND_OPTION_ENCRYPT,
                null,
                InputOption::VALUE_NONE,
                'The config value should be encrypted using local.xml\'s crypt key'
            )
            ->addOption(
                self::COMMAND_OPTION_FORCE,
                null,
                InputOption::VALUE_NONE,
                'Allow creation of non-standard scope-id\'s for websites and stores'
            )
            ->addOption(
                self::COMMAND_OPTION_NO_NULL,
                null,
                InputOption::VALUE_NONE,
                "Do not treat value NULL as " . self::DISPLAY_NULL_UNKNOWN_VALUE . " value"
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Set a store config value by path.
To set a value of a specify store view you must set the "scope" and "scope-id" option.

HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $config = $this->_getConfigModel();
        if (!$config->getResourceModel()) {
            // without a resource model, a config option can't be saved.
            return Command::FAILURE;
        }

        /** @var bool $allowZeroScope */
        $allowZeroScope = $input->getOption(self::COMMAND_OPTION_FORCE);
        /** @var string $scope */
        $scope = $input->getOption(self::COMMAND_OPTION_SCOPE);
        /** @var string $scopeId */
        $scopeId = $input->getOption(self::COMMAND_OPTION_SCOPE_ID);

        $this->_validateScopeParam($scope);
        $scopeId = (int) $this->_convertScopeIdParam($scope, $scopeId, $allowZeroScope);

        /** @var string $value */
        $value = $input->getArgument(self::COMMAND_ARGUMENT_VALUE);
        $valueDisplay = $value;

        if ($value === "NULL" && !$input->getOption(self::COMMAND_OPTION_NO_NULL)) {
            if ($input->getOption(self::COMMAND_OPTION_ENCRYPT)) {
                throw new InvalidArgumentException("Encryption is not possible for NULL values");
            }
            $value = null;
            $valueDisplay = self::DISPLAY_NULL_UNKNOWN_VALUE;
        } else {
            $value = str_replace(['\n', '\r'], ["\n", "\r"], $value);
            $value = $this->_formatValue($value, ($input->getOption(self::COMMAND_OPTION_ENCRYPT) ? 'encrypt' : false));
        }

        /** @var string $path */
        $path = $input->getArgument(self::COMMAND_ARGUMENT_PATH);
        /** @phpstan-ignore argument.type (Parameter #2 $value of method Mage_Core_Model_Config::saveConfig() expects string, string|null given @TODO(sr)) */
        $config->saveConfig($path, $value, $scope, $scopeId);

        $output->writeln(sprintf(
            '<comment>%s</comment> => <comment>%s</comment>',
            $path,
            $valueDisplay
        ));

        return Command::SUCCESS;
    }
}
