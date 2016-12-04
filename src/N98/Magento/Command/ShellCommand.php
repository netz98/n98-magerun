<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Shell;

class ShellCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('shell')
            ->setDescription('Runs n98-magerun as shell')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shell = new Shell($this->getApplication());
        $shell->run();
    }
}
