<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package N98\Magento\Command\System
 */
class MaintenanceCommand extends AbstractMagentoCommand
{
    private const COMMAND_OPTION_ON = 'on';
    private const COMMAND_OPTION_OFF = 'off';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:maintenance';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles maintenance mode';

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_ON,
                null,
                InputOption::VALUE_NONE,
                'Enable maintenance mode'
            )
            ->addOption(
                self::COMMAND_OPTION_OFF,
                null,
                InputOption::VALUE_NONE,
                'Disable maintenance mode'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $flagFile = $this->_magentoRootFolder . '/maintenance.flag';

        if ($input->getOption(self::COMMAND_OPTION_OFF)) {
            $this->switchOff($output, $flagFile);
        } elseif ($input->getOption(self::COMMAND_OPTION_ON)) {
            $this->switchOn($output, $flagFile);
        } else {
            if (file_exists($flagFile)) {
                $this->switchOff($output, $flagFile);
            } else {
                $this->switchOn($output, $flagFile);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param string $flagFile
     */
    private function switchOn(OutputInterface $output, string $flagFile): void
    {
        if (!file_exists($flagFile)) {
            if (!touch($flagFile)) {
                throw new RuntimeException('maintenance.flag file is not writable.');
            }
        }
        $output->writeln('Maintenance mode <info>on</info>');
    }

    /**
     * @param OutputInterface $output
     * @param string $flagFile
     */
    private function switchOff(OutputInterface $output, string $flagFile): void
    {
        if (file_exists($flagFile)) {
            if (!unlink($flagFile)) {
                throw new RuntimeException('maintenance.flag file is not removable.');
            }
        }
        $output->writeln('Maintenance mode <info>off</info>');
    }
}
