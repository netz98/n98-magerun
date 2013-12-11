<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class AbstractMagentoStoreConfigCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     */
    const SCOPE_STORE_VIEW = 'store';

    /**
     * @var string
     */
    const SCOPE_WEBSITE = 'website';

    /**
     * @var string
     */
    const SCOPE_GLOBAL = 'global';

    /**
     * Store view or global by additional option
     */
    const SCOPE_STORE_VIEW_GLOBAL = 'store_view_global';

    /**
     * Set 1/0 for on and off
     */
    const COMMAND_TYPE_TOGGLE = 1;

    /**
     * Set a value
     */
    const COMMAND_TYPE_VALUE = 2;

    /**
     * Set multiple values separated by comma and accept them using multiple 
     * option values.
     */
    const COMMAND_TYPE_COMMA = 4;

    /**
     * Support clearing a value
     */
    const COMMAND_TYPE_SUPPORT_CLEAR = 4096;

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
     * Type of command:
     * - toggle: sets a setting true/false
     * - value: sets a single value on the configuration
     * - comma: sets a comma-separated value, accepted as multiple options
     *
     * TODO?
     * - json: accepts and verifies a json string from stdin.
     *   Fooman/Surcharge is one extension that stores it's configuration that 
     *   way.
     */
    protected $commandType = self::COMMAND_TYPE_TOGGLE;

    /**
     * @deprecated
     * @see deleteWithClear
     *
     * If set to 'off' delete key from core_config_data
     */
    protected $deleteWithOff = false;

    /**
     * If clearing is enabled, implement clearing by deleting the value.
     *
     * NOTE: this means the default for this setting has to be the empty 
     * string or correspond to the 'off' setting.
     */
    protected $deleteWithClear = false;

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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $runOnStoreView = false;
            if ($this->scope == self::SCOPE_STORE_VIEW
                || ($this->scope == self::SCOPE_STORE_VIEW_GLOBAL && !$input->getOption('global'))
            )
            {
                $runOnStoreView = true;
            }

            if ($runOnStoreView) {
                $store = $this->_initStore($input, $output);
            } else {
                $store = \Mage::app()->getStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
            }
        }

        if ($input->getOption('on')) {
            $isFalse = true;
        } elseif ($input->getOption('off')) {
            $isFalse = false;
        } else {
            $isFalse = !\Mage::getStoreConfigFlag($this->configPath, $store->getId());
        }

        $this->_beforeSave($store, $isFalse);

        \Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $isFalse ? 1 : 0,
            $store->getId() == \Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId()
        );

        $comment = '<comment>' . $this->toggleComment . '</comment> '
                 . '<info>' . (!$isFalse ? $this->falseName : $this->trueName) . '</info>'
                 . ($runOnStoreView ? ' <comment>for store</comment> <info>' . $store->getCode() . '</info>' : '');
        $output->writeln($comment);

        $this->_afterSave($store, $isFalse);

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     * @throws \Exception
     */
    protected function _initStore($input, $output)
    {
        return $this->getHelperSet()->get('parameter')->askStore($input, $output, 'store', $this->withAdminStore);
    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _beforeSave(\Mage_Core_Model_Store $store, $disabled)
    {

    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _afterSave(\Mage_Core_Model_Store $store, $disabled)
    {

    }
}
