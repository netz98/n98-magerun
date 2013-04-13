<?php

namespace N98MagerunTest;

class TestDummyCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('n98mageruntest:test:dummy')
            ->setDescription('Dummy command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // do nothing
    }
}