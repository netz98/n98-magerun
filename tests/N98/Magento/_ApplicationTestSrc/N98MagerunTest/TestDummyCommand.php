<?php

declare(strict_types=1);

namespace N98MagerunTest;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDummyCommand extends AbstractMagentoCommand
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
            return Command::INVALID;
        }

        $output->writeln('dummy');
        return Command::SUCCESS;
    }
}
