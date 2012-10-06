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

        $disabled = !\Mage::getStoreConfigFlag($this->configPath, $store->getId());
        \Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $disabled ? 1 : 0,
            $store->getId() == \Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId()
        );

        $comment = '<comment>' . $this->toggleComment . '</comment> '
                 . '<info>' . (!$disabled ? 'disabled' : 'enabled') . '</info>'
                 . ($this->scope == self::SCOPE_STORE_VIEW ? ' <comment>for store</comment> <info>' . $store->getCode() . '</info>' : '');
        $output->writeln($comment);

        $input = new StringInput('cache:clear');
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
        try {
            if ($input->getArgument('store') === null) {
                throw new \Exception('No store given');
            }
            $store = \Mage::app()->getStore($input->getArgument('store'));
        } catch (\Exception $e) {
            $i = 0;
            foreach (\Mage::app()->getStores() as $store) {
                $stores[$i ] = $store->getId();
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $store->getCode() . ' - ' . $store->getName() . PHP_EOL;
                $i++;
            }
            $question[] = '<question>Please select a store: </question>';

            $storeId = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($stores) {
                if (!isset($stores[$typeInput - 1])) {
                    throw new \InvalidArgumentException('Invalid store');
                }
                return $stores[$typeInput - 1];
            });

            $store = \Mage::app()->getStore($storeId);
        }

        return $store;
    }
}