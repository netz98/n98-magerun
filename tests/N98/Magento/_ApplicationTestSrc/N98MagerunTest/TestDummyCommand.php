<?php

namespace N98MagerunTest;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $output->writeln('dummy');
    }
}
