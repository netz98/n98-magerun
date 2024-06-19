<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use N98\Util\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Log filesize customer command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class SizeCommand extends AbstractLogCommand
{
    public const COMMAND_ARGUMENT_LOG_FILENAME = 'log_filename';

    public const COMMAND_OPTION_HUMAN = 'human';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:log:size';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Get size of log file.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_LOG_FILENAME,
                InputArgument::OPTIONAL,
                'Name of log file.'
            )
            ->addOption(
                self::COMMAND_OPTION_HUMAN,
                null,
                InputOption::VALUE_NONE,
                'Human readable output'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $fileName = $input->getArgument(self::COMMAND_ARGUMENT_LOG_FILENAME);
        if ($fileName === null) {
            /** @var string $path */
            $path = $this->askLogFile($input, $output);
        } else {
            $path = $this->getLogDir() . DIRECTORY_SEPARATOR . $fileName;
        }

        $size = 0;
        if ($path && $this->logfileExists(basename($path))) {
            $size = @filesize($path);

            if ($size === false) {
                throw new RuntimeException('Couldn\t detect filesize.');
            }
        } else {
            $output->writeln('<info>Logfile not found.</info>');
            $path = '';
        }

        if ($path) {
            if ($input->getOption(self::COMMAND_OPTION_HUMAN)) {
                $output->writeln(Filesystem::humanFileSize($size));
            } else {
                $output->writeln("$size");
            }
        }

        return Command::SUCCESS;
    }
}
