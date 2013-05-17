<?php

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class SizeCommand extends AbstractLogCommand
{
    protected function configure()
    {
        $this->setName('dev:log:size')
             ->addArgument('log_filename', InputArgument::OPTIONAL, 'Name of log file.')
             ->setDescription('Get size of log file');
    }
    
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $fileName = $input->getArgument('log_filename');
            if ($fileName === null) {
                $path = $this->askLogFile($output);
            } else {
                $path = $this->getLogDir() . DIRECTORY_SEPARATOR . $fileName;
            }

            if ($this->logfileExists(basename($path))) {
                $size = @filesize($path);

                if ($size === false) {
                    throw new \RuntimeException('Couldn\t detect filesize.');
                }
            } else {
                $size = 0;
            }

            $output->writeln("$size");
        }
    }
}
