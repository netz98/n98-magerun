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

class TemplateHintsBlocksCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:template-hints-blocks')
            ->addArgument('store', InputArgument::REQUIRED, 'Store code or ID')
            ->setDescription('Toggles template hints block names')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @TODO move shop init code into own base class */
        $this->detectMagento($output);
        if ($this->initMagento()) {
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
        }

        $enabled = \Mage::getStoreConfigFlag('dev/debug/template_hints_blocks', $store->getId());
        \Mage::app()->getConfig()->saveConfig('dev/debug/template_hints_blocks', $enabled ? 0 : 1, 'stores', $store->getId());

        $output->writeln('<info>Template Hints Blocks ' . (!$enabled ? 'enabled' : 'disabled') . '</info>');

        $input = new StringInput('cache:clear');
        $this->getApplication()->run($input, new NullOutput());
    }
}