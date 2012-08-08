<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('maintenance')
            ->setDescription('Toggles maintenance mode.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $flagFile = $this->_magentoRootFolder . '/maintenance.flag';
        if (file_exists($flagFile)) {
            unlink($flagFile);
            $output->writeln('Maintenance mode <info>off</info>');
        } else {
            touch($flagFile);
            $output->writeln('Maintenance mode <info>on</info>');
        }
    }
}