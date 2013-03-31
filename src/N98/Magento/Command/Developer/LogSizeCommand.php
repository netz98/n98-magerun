<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class LogSizeCommand extends AbstractMagentoCommand
{
    protected $_input = null;
    protected $_output = null;
    
    protected function configure()
    {
        $this->setName('dev:log:size')
             ->addArgument('log_filename', InputArgument::REQUIRED, 'Name of log file.')
             ->setDescription('Get size of log file');
    }
    
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->detectMagento($output);
        $this->initMagento();
        
        $dir = \Mage::getBaseDir('log');
        $fileName = $input->getArgument('log_filename');
        $path = $dir . DS . $fileName;
        
        if (file_exists($path)) {
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
