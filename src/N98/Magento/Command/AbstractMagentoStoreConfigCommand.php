<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW;

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription)
        ;

        if ($this->scope == self::SCOPE_STORE_VIEW) {
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
            if ($this->scope == self::SCOPE_STORE_VIEW) {
                $store = $this->_initStore($input, $output);
            } else {
                $store = \Mage::app()->getStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
            }
        }

        $isFalse = !\Mage::getStoreConfigFlag($this->configPath, $store->getId());

        $this->_beforeSave($store, $isFalse);

        \Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $isFalse ? 1 : 0,
            $store->getId() == \Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId()
        );

        $comment = '<comment>' . $this->toggleComment . '</comment> '
                 . '<info>' . (!$isFalse ? $this->falseName : $this->trueName) . '</info>'
                 . ($this->scope == self::SCOPE_STORE_VIEW ? ' <comment>for store</comment> <info>' . $store->getCode() . '</info>' : '');
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
        return $this->getHelperSet()->get('parameter')->askStore($input, $output);
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