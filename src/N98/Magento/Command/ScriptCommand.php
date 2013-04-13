<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('script')
            ->addArgument('filename', InputArgument::REQUIRED, 'Script file')
            ->setDescription('Runs multiple n98-magerun commands')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $script = \file_get_contents($input->getArgument('filename'));
        $commands = explode("\n", $script);
        foreach ($commands as $commandString) {
            if (empty($commandString) || substr($commandString, 0, 1) === '#') {
                continue;
            }
            $this->getApplication()->setAutoExit(false);
            $input = new StringInput($commandString);
            $this->getApplication()->run($input, $output);
        }
    }
}
