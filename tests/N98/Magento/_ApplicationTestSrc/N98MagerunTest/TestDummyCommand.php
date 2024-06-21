<?php

namespace N98MagerunTest;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDummyCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('n98mageruntest:test:dummy')
            ->setDescription('Dummy command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $output->writeln('dummy');
        return 0;
    }
}
