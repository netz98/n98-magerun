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
            ->setName('sys:maintenance')
            ->addOption('on', null, InputOption::VALUE_NONE, 'Force maintenance mode')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
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

        if ($input->getOption('off')) {
            $this->_switchOff($output, $flagFile);
        } elseif ($input->getOption('on')) {
            $this->_switchOn($output, $flagFile);
        } else {
            if (file_exists($flagFile)) {
                $this->_switchOff($output, $flagFile);
            } else {
                $this->_switchOn($output, $flagFile);
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $flagFile
     */
    protected function _switchOn(OutputInterface $output, $flagFile)
    {
        touch($flagFile);
        $output->writeln('Maintenance mode <info>on</info>');
    }

    /**
     * @param OutputInterface $output
     * @param string $flagFile
     */
    protected function _switchOff($output, $flagFile)
    {
        if (file_exists($flagFile)) {
            unlink($flagFile);
        }
        $output->writeln('Maintenance mode <info>off</info>');
    }
}