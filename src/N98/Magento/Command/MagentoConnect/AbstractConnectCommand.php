<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractConnectCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     */
    protected $mageScript = null;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function findMageScript(InputInterface $input, OutputInterface $output)
    {
        if ($this->mageScript === null) {
            $this->detectMagento($output);
            $this->mageScript = $this->_magentoRootFolder . DIRECTORY_SEPARATOR . 'mage';
            if (!is_file($this->mageScript)) {
                throw new \Exception('Could not find "mage" shell script in current installation');
            }
            if (!is_executable($this->mageScript)) {
                if (!@chmod($this->mageScript, 0755)) {
                    throw new \Exception('Cannot make "mage" shell script executable. Please chmod the file manually.');
                }
            }
        }
    }

    /**
     * @param string $line
     * @return array
     */
    protected function matchConnectLine($line)
    {
        $matches = array();
        preg_match('/([a-zA-Z0-9-_]+):\s([0-9.]+)\s([a-z]+)/', $line, $matches);
        return $matches;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $mageScriptParams
     * @return string
     */
    protected function callMageScript(InputInterface $input, OutputInterface $output, $mageScriptParams)
    {
        $this->findMageScript($input, $output);
        return shell_exec($this->mageScript . ' ' . $mageScriptParams);
    }
}