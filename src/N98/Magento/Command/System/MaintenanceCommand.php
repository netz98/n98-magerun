<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Toggles maintenance mode command
 *
 * @package N98\Magento\Command\System
 */
class MaintenanceCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:maintenance')
            ->addOption('on', null, InputOption::VALUE_NONE, 'Enable maintenance mode')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
            ->setDescription('Toggles maintenance mode.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $flagFile = $this->_magentoRootFolder . '/maintenance.flag';

        if ($input->getOption('off')) {
            $this->_switchOff($output, $flagFile);
        } elseif ($input->getOption('on')) {
            $this->_switchOn($output, $flagFile);
        } elseif (file_exists($flagFile)) {
            $this->_switchOff($output, $flagFile);
        } else {
            $this->_switchOn($output, $flagFile);
        }

        return Command::SUCCESS;
    }

    protected function _switchOn(OutputInterface $output, string $flagFile): void
    {
        if (!file_exists($flagFile) && !touch($flagFile)) {
            throw new RuntimeException('maintenance.flag file is not writable.');
        }

        $output->writeln('Maintenance mode <info>on</info>');
    }

    protected function _switchOff(OutputInterface $output, string $flagFile): void
    {
        if (file_exists($flagFile) && !unlink($flagFile)) {
            throw new RuntimeException('maintenance.flag file is not removable.');
        }

        $output->writeln('Maintenance mode <info>off</info>');
    }
}
