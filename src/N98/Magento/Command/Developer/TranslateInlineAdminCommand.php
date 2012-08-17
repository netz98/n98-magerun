<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

class TranslateInlineAdminCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:translate:admin')
            ->setDescription('Toggle inline translation tool for admin')
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
            $enabled = \Mage::getStoreConfigFlag('dev/translate_inline/active_admin');
            \Mage::app()->getConfig()->saveConfig('dev/translate_inline/active_admin', $enabled ? 0 : 1, 'global');

            $output->writeln('<info>Inline Translation ' . (!$enabled ? 'enabled' : 'disabled') . '</info>');

            $this->getApplication()->get('cache:clear')->run($input, new NullOutput());
        }
    }
}