<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCountCommand extends AbstractMagentoCommand
{
    protected $_input = null;
    protected $_output = null;
    
    protected function configure()
    {
        $this->setName('dev:report:count')
             ->setDescription('Get count of report files');
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
        
        $dir = \Mage::getBaseDir('var') . DS . 'report' . DS;
        $count = $this->getFileCount($dir);
        
        $output->writeln("$count");
    }
    
    /**
     * Returns the number of files in the directory.
     * 
     * @param string $path Path to the directory
     * @return int
     */
    protected function getFileCount($path)
    {
        $result = 0;
        
        if (file_exists($path) && is_dir($path)) {
            foreach (new \DirectoryIterator($path) as $entry) {
                if ($entry->isFile()) {
                    $result++;
                }
            }    
        }
        
        return $result;
    }
}
