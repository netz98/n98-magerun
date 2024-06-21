<?php

namespace TestModule;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('testmodule:foo')
            ->setDescription('Test command registered in a module');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }
        return 0;
    }
}
