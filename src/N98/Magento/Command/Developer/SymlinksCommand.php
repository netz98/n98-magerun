<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Cache\ClearCommand as ClearCacheCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

class SymlinksCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:symlinks')
            ->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID')
            ->setDescription('Toggle allow symlinks setting')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = null;
        /* @TODO move shop init code into own base class */
        $this->detectMagento($output);
        if ($this->initMagento()) {
            try {
                $store = \Mage::app()->getStore($input->getArgument('store'));
                $storeId = $store->getId();
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
        }

        if ($storeId > 0) {
            $allowed = \Mage::getStoreConfigFlag('dev/template/allow_symlink', $storeId);
            \Mage::app()->getConfig()->saveConfig('dev/template/allow_symlink', $allowed ? 0 : 1, 'stores', $storeId);
            $output->writeln('<info>Symlinks <comment>' . (!$allowed ? 'allowed' : 'denied') . '</comment> for store ' . $store->getCode() . '</info>');
        } else {
            $allowed = intval(\Mage::app()->getConfig()->getNode('dev/template/allow_symlink', 'default'));
            \Mage::app()->getConfig()->saveConfig('dev/template/allow_symlink', $allowed ? 0 : 1, 'default');
            $output->writeln('<info>Symlinks globally <comment>' . (!$allowed ? 'allowed' : 'denied') . '</comment></info>');
        }


        $this->getApplication()->get('cache:flush')->run($input, new NullOutput());
    }
}