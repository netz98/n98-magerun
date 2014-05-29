<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
