<?php

namespace Acme;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this->setName('acme:foo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
