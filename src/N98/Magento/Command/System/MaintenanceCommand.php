<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @package N98\Magento\Command\System
 */
class MaintenanceCommand extends AbstractCommand
{
    public const COMMAND_OPTION_OFF = 'off';

    public const COMMAND_OPTION_ON = 'on';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:maintenance';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles maintenance mode.';

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    public function __construct()
    {
        parent:: __construct();
        $this->filesystem = new Filesystem();
    }

    /**
     * @return void
     */
    protected function configure(): void
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
            if ($this->filesystem->exists($flagFile)) {
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
        try {
            $this->filesystem->touch($flagFile);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }

        $output->writeln('Maintenance mode <info>on</info>');
    }

    /**
     * @param OutputInterface $output
     * @param string $flagFile
     */
    private function switchOff(OutputInterface $output, string $flagFile): void
    {
        try {
            $this->filesystem->remove($flagFile);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }

        $output->writeln('Maintenance mode <info>off</info>');
    }
}
