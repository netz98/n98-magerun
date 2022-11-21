<?php

namespace N98\Magento\Command;

use Mage;
use Mage_Core_Model_App;
use Mage_Core_Model_Store;
use N98\Util\Console\Helper\IoHelper;
use N98\Util\Console\Helper\ParameterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractMagentoStoreConfigCommand extends AbstractMagentoCommand
{
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

    /**
     * @var string
     */
    protected $commandName = '';

    /**
     * @var string
     */
    protected $commandDescription = '';

    /**
     * @var string
     */
    protected $configPath = '';

    /**
     * @var string
     */
    protected $toggleComment = '';

    /**
     * @var string
     */
    protected $falseName = 'disabled';

    /**
     * @var string
     */
    protected $trueName = 'enabled';

    /**
     * Add admin store to interactive prompt
     *
     * @var bool
     */
    protected $withAdminStore = false;

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW;

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->addOption('on', null, InputOption::VALUE_NONE, 'Switch on')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Switch off')
            ->setDescription($this->commandDescription)
        ;

        if ($this->scope == self::SCOPE_STORE_VIEW_GLOBAL) {
            $this->addOption('global', null, InputOption::VALUE_NONE, 'Set value on default scope');
        }

        if ($this->scope == self::SCOPE_STORE_VIEW || $this->scope == self::SCOPE_STORE_VIEW_GLOBAL) {
            $this->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = null;
        $runOnStoreView = null;
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $runOnStoreView = false;
            if ($this->scope == self::SCOPE_STORE_VIEW
                || ($this->scope == self::SCOPE_STORE_VIEW_GLOBAL && !$input->getOption('global'))
            ) {
                $runOnStoreView = true;
            }

            if ($runOnStoreView) {
                $store = $this->_initStore($input, $output);
            } else {
                $store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);
            }
        }

        if ($input->getOption('on')) {
            $isFalse = true;
        } elseif ($input->getOption('off')) {
            $isFalse = false;
        } else {
            $isFalse = !Mage::getStoreConfigFlag($this->configPath, $store->getId());
        }

        $this->_beforeSave($store, $isFalse);

        Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $isFalse ? 1 : 0,
            $store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId()
        );

        $comment =
            '<comment>' . $this->toggleComment . '</comment> '
            . '<info>' . (!$isFalse ? $this->falseName : $this->trueName) . '</info>'
            . ($runOnStoreView ? ' <comment>for store</comment> <info>' . $store->getCode() . '</info>' : '');

        $output->writeln($comment);

        $this->_afterSave($store, $isFalse);

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());
        return 0;
    }

    /**
     * Determine if a developer restriction is in place, and if we're enabling something that will use it
     * then notify and ask if it needs to be changed from its current value.
     *
     * @param  \Mage_Core_Model_Store $store
     * @param  bool                   $enabled
     * @return void
     */
    protected function detectAskAndSetDeveloperIp(Mage_Core_Model_Store $store, $enabled)
    {
        if (!$enabled) {
            // No need to notify about developer IP restrictions if we're disabling template hints etc
            return;
        }

        if (!$devRestriction = $store->getConfig('dev/restrict/allow_ips')) {
            return;
        }

        /** @var IoHelper $helper */
        $helper = $this->getHelper('io');
        $this->askAndSetDeveloperIp($helper->getInput(), $helper->getOutput(), $store, $devRestriction);
    }

    /**
     * Ask if the developer IP should be changed, and change it if required
     *
     * @param  InputInterface        $input
     * @param  OutputInterface        $output
     * @param  \Mage_Core_Model_Store $store
     * @param  string|null            $devRestriction
     * @return void
     */
    protected function askAndSetDeveloperIp(InputInterface $input, OutputInterface $output, Mage_Core_Model_Store $store, $devRestriction)
    {
        $output->writeln(
            sprintf(
                '<comment><info>Please note:</info> developer IP restriction is enabled for <info>%s</info>.',
                $devRestriction
            )
        );

        /** @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');
        $question = new Question('<question>Change developer IP? Enter a new IP to change or leave blank: </question>');
        $newDeveloperIp = $dialog->ask($input, $output, $question);

        if (empty($newDeveloperIp)) {
            return;
        }

        $this->setDeveloperIp($store, $newDeveloperIp);
        $output->writeln(sprintf('<comment><info>New developer IP restriction set to %s', $newDeveloperIp));
    }

    /**
     * Set the restricted IP for developer access
     *
     * @param \Mage_Core_Model_Store $store
     * @param string                 $newDeveloperIp
     */
    protected function setDeveloperIp(Mage_Core_Model_Store $store, $newDeveloperIp)
    {
        Mage::getModel('core/config')
            ->saveConfig('dev/restrict/allow_ips', $newDeveloperIp, 'stores', $store->getId());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    protected function _initStore(InputInterface $input, OutputInterface $output)
    {
        /** @var ParameterHelper $parameterHelper */
        $parameterHelper = $this->getHelper('parameter');

        return $parameterHelper->askStore($input, $output, 'store', $this->withAdminStore);
    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _beforeSave(Mage_Core_Model_Store $store, $disabled)
    {
    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _afterSave(Mage_Core_Model_Store $store, $disabled)
    {
    }
}
