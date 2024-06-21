<?php

namespace Acme;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('acme:foo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}
