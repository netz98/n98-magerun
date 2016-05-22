<?php

namespace N98\Magento\Command\Developer\Log;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SizeCommand extends AbstractLogCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:log:size')
            ->addArgument('log_filename', InputArgument::OPTIONAL, 'Name of log file.')
            ->addOption('human', '', InputOption::VALUE_NONE, 'Human readable output')
            ->setDescription('Get size of log file');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws RuntimeException
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $fileName = $input->getArgument('log_filename');
        if ($fileName === null) {
            $path = $this->askLogFile($output);
        } else {
            $path = $this->getLogDir() . DIRECTORY_SEPARATOR . $fileName;
        }

        if ($this->logfileExists(basename($path))) {
            $size = @filesize($path);

            if ($size === false) {
                throw new RuntimeException('Couldn\t detect filesize.');
            }
        } else {
            $size = 0;
        }

        if ($input->getOption('human')) {
            $output->writeln(\N98\Util\Filesystem::humanFileSize($size));
        } else {
            $output->writeln("$size");
        }
    }
}
