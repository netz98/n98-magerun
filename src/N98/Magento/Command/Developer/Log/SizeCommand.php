<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use N98\Util\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get log size command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class SizeCommand extends AbstractLogCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:log:size')
            ->addArgument('log_filename', InputArgument::OPTIONAL, 'Name of log file.')
            ->addOption('human', '', InputOption::VALUE_NONE, 'Human readable output')
            ->setDescription('Get size of log file');
    }

    /**
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $fileName = $input->getArgument('log_filename');
        $path = $fileName === null ? $this->askLogFile($input, $output) : $this->getLogDir() . DIRECTORY_SEPARATOR . $fileName;

        if ($this->logfileExists(basename($path))) {
            $size = @filesize($path);

            if ($size === false) {
                throw new RuntimeException('Couldn\t detect filesize.');
            }
        } else {
            $size = 0;
        }

        if ($input->getOption('human')) {
            $output->writeln(Filesystem::humanFileSize($size));
        } else {
            $output->writeln('' . $size);
        }

        return Command::SUCCESS;
    }
}
