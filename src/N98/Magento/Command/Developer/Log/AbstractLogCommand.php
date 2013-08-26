<?php

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class AbstractLogCommand extends AbstractMagentoCommand
{
    /**
     * @return Finder
     */
    protected function getLogFileIterator()
    {
        $finder = Finder::create();
        $finder->ignoreUnreadableDirs(true);

        $logDirs = array(
            $this->getLogDir(),
        );

        if (is_dir($this->getDebugDir())) {
            $logDirs[] = $this->getDebugDir();
        }

        return $finder->files()->in($logDirs);
    }

    /**
     * @return string
     */
    protected function getLogDir()
    {
        return \Mage::getBaseDir('log');
    }

    /**
     * @return string
     */
    protected function getDebugDir()
    {
        return \Mage::getBaseDir('var') . '/debug';
    }

    /**
     * @param string $filename
     * @return bool
     */
    protected function logfileExists($filename)
    {
        $iterator = $this->getLogFileIterator();
        return $iterator->name(basename($filename))->count() == 1;
    }

    /**
     * @param $output OutputInterface
     * @return string
     */
    protected function askLogFile($output)
    {
        $logFiles = $this->getLogFileIterator();
        $i = 0;
        foreach ($logFiles as $logFile) {
            $files[$i] = $logFile->getPathname();
            $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $logFile->getFilename() . PHP_EOL;
            $i++;
        }
        $question[] = '<question>Please select a log file: </question>';

        if (count($logFiles) == 0) {
            return '';
        }

        $logFile = $this->getHelperSet()->get('dialog')->askAndValidate($output, $question, function($typeInput) use ($files) {
            if (!isset($files[$typeInput - 1])) {
                throw new \InvalidArgumentException('Invalid file');
            }

            return $files[$typeInput - 1];
        });

        return $logFile;
    }
}