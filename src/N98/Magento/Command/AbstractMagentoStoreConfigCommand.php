<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Exception;
use Mage;
use Mage_Core_Model_App;
use Mage_Core_Model_Config;
use Mage_Core_Model_Store;
use Mage_Core_Model_Store_Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AbstractMagentoStoreConfigCommand
 *
 * @package N98\Magento\Command
 */
abstract class AbstractMagentoStoreConfigCommand extends AbstractMagentoCommand
{
    public string $commandName = '';

    public string $commandDescription = '';

    public const COMMAND_ARGUMENT_STORE = 'store';

    public const COMMAND_OPTION_OFF = 'off';

    public const COMMAND_OPTION_ON = 'on';

    public const COMMAND_OPTION_GLOBAL = 'global';

    /**
     * @var string
     */
    public const SCOPE_STORE_VIEW = 'store';

    /**
     * @var string
     */
    public const SCOPE_WEBSITE = 'website';

    /**
     * @var string
     */
    public const SCOPE_GLOBAL = 'global';

    /**
     * Store view or global by additional option
     */
    public const SCOPE_STORE_VIEW_GLOBAL = 'store_view_global';

    protected string $configPath = '';

    protected string $toggleComment = '';

    protected string $falseName = 'disabled';

    protected string $trueName = 'enabled';

    /**
     * Add admin store to interactive prompt
     */
    protected bool $withAdminStore = false;

    protected string $scope = self::SCOPE_STORE_VIEW;

    protected function configure(): void
    {
        // for backwards compatibility before v3.0
        // @phpstan-ignore function.alreadyNarrowedType
        if (property_exists($this, 'commandName') && $this->commandName) {
            $this->setName($this->commandName);
        }

        // for backwards compatibility before v3.0
        // @phpstan-ignore function.alreadyNarrowedType
        if (property_exists($this, 'commandDescription') && $this->commandDescription) {
            $this->setDescription($this->commandDescription);
        }

        $this
            ->addOption(
                self::COMMAND_OPTION_ON,
                null,
                InputOption::VALUE_NONE,
                'Switch on',
            )
            ->addOption(
                self::COMMAND_OPTION_OFF,
                null,
                InputOption::VALUE_NONE,
                'Switch off',
            )
        ;

        if ($this->scope === self::SCOPE_STORE_VIEW_GLOBAL) {
            $this->addOption(
                self::COMMAND_OPTION_GLOBAL,
                null,
                InputOption::VALUE_NONE,
                'Set value on default scope',
            );
        }

        if ($this->scope === self::SCOPE_STORE_VIEW || $this->scope === self::SCOPE_STORE_VIEW_GLOBAL) {
            $this->addArgument(
                self::COMMAND_ARGUMENT_STORE,
                InputArgument::OPTIONAL,
                'Store code or ID',
            );
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // for backwards compatibility before v3.0
        // @phpstan-ignore function.alreadyNarrowedType
        if (property_exists($this, 'commandName')) {
            $output->writeln('<warning>Property "commandName" is deprecated, use "public static $defaultName"</warning>');
        }

        // for backwards compatibility before v3.0
        // @phpstan-ignore function.alreadyNarrowedType
        if (property_exists($this, 'commandDescription')) {
            $output->writeln('<warning>Property "commandDescription" is deprecated, use "public static $defaultDescription"</warning>');
        }

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Model_Store_Exception
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        $runOnStoreView = false;
        if ($this->scope === self::SCOPE_STORE_VIEW
            || ($this->scope === self::SCOPE_STORE_VIEW_GLOBAL && !$input->getOption(self::COMMAND_OPTION_GLOBAL))
        ) {
            $runOnStoreView = true;
        }

        if ($runOnStoreView) {
            $store = $this->_initStore($input, $output);
        } else {
            $store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        }

        if ($input->getOption(self::COMMAND_OPTION_ON)) {
            $isFalse = true;
        } elseif ($input->getOption(self::COMMAND_OPTION_OFF)) {
            $isFalse = false;
        } else {
            $isFalse = !Mage::getStoreConfigFlag($this->configPath, $store->getId());
        }

        $this->_beforeSave($store, $isFalse);

        Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $isFalse ? '1' : '0',
            $store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId(),
        );

        $comment =
            '<comment>' . $this->toggleComment . '</comment> '
            . '<info>' . ($isFalse ? $this->trueName : $this->falseName) . '</info>'
            . ($runOnStoreView ? ' <comment>for store</comment> <info>' . $store->getCode() . '</info>' : '');

        $output->writeln($comment);

        $this->_afterSave($store, $isFalse);

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());

        return Command::SUCCESS;
    }

    /**
     * Determine if a developer restriction is in place, and if we're enabling something that will use it
     * then notify and ask if it needs to be changed from its current value.
     */
    protected function detectAskAndSetDeveloperIp(Mage_Core_Model_Store $mageCoreModelStore, bool $enabled): void
    {
        if (!$enabled) {
            // No need to notify about developer IP restrictions if we're disabling template hints etc
            return;
        }

        if (!$devRestriction = $mageCoreModelStore->getConfig('dev/restrict/allow_ips')) {
            return;
        }

        $ioHelper = $this->getIoHelper();
        $this->askAndSetDeveloperIp($ioHelper->getInput(), $ioHelper->getOutput(), $mageCoreModelStore, $devRestriction);
    }

    /**
     * Ask if the developer IP should be changed, and change it if required
     */
    protected function askAndSetDeveloperIp(
        InputInterface        $input,
        OutputInterface       $output,
        Mage_Core_Model_Store $mageCoreModelStore,
        ?string               $devRestriction
    ): void {
        $output->writeln(
            sprintf(
                '<comment><info>Please note:</info> developer IP restriction is enabled for <info>%s</info>.',
                $devRestriction,
            ),
        );

        $questionHelper = $this->getQuestionHelper();
        $question = new Question('<question>Change developer IP? Enter a new IP to change or leave blank:</question> ');
        /** @var string $newDeveloperIp */
        $newDeveloperIp = $questionHelper->ask($input, $output, $question);

        if (empty($newDeveloperIp)) {
            return;
        }

        $this->setDeveloperIp($mageCoreModelStore, $newDeveloperIp);
        $output->writeln(sprintf(
            '<comment><info>New developer IP restriction set to %s</info></comment>',
            $newDeveloperIp,
        ));
    }

    /**
     * Set the restricted IP for developer access
     */
    protected function setDeveloperIp(Mage_Core_Model_Store $mageCoreModelStore, string $newDeveloperIp): void
    {
        /** @var Mage_Core_Model_Config $model */
        $model = Mage::getModel('core/config');
        $model->saveConfig('dev/restrict/allow_ips', $newDeveloperIp, 'stores', $mageCoreModelStore->getId());
    }

    /**
     *
     * @return mixed
     */
    protected function _initStore(InputInterface $input, OutputInterface $output)
    {
        $parameterHelper = $this->getParameterHelper();
        return $parameterHelper->askStore($input, $output, self::COMMAND_ARGUMENT_STORE, $this->withAdminStore);
    }

    protected function _beforeSave(Mage_Core_Model_Store $mageCoreModelStore, bool $disabled): void {}

    protected function _afterSave(Mage_Core_Model_Store $mageCoreModelStore, bool $disabled): void {}
}
