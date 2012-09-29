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
            $this->addArgument('store', InputArgument::REQUIRED, 'Store code or ID');
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
                try {
                    $store = \Mage::app()->getStore($input->getArgument('store'));
                } catch (\Mage_Core_Exception $e) {
                    $output->writeln(array(
                        '<error>Invalid store</error>',
                        '<info>Try one of this:</info>'
                    ));
                    foreach (\Mage::app()->getStores() as $store) {
                        $output->writeln('- <comment>' . $store->getCode() . '</comment>');
                    }
                    return;
                }
            } else {
                $store = \Mage::app()->getStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
            }
        }

        $enabled = \Mage::getStoreConfigFlag($this->configPath, $store->getId());
        \Mage::app()->getConfig()->saveConfig(
            $this->configPath,
            $enabled ? 0 : 1,
            $store->getId() == \Mage_Core_Model_App::ADMIN_STORE_ID ? 'default' : 'stores',
            $store->getId()
        );

        $output->writeln('<comment>' . $this->toggleComment . '</comment> <info>' . (!$enabled ? 'enabled' : 'disabled') . '</info>');

        $input = new StringInput('cache:clear');
        $this->getApplication()->run($input, new NullOutput());
    }
}