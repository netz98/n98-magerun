<?php

namespace N98\Magento\Command\Developer\Log;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
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

        $logDirs = [$this->getLogDir()];

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
        return Mage::getBaseDir('log');
    }

    /**
     * @return string
     */
    protected function getDebugDir()
    {
        return Mage::getBaseDir('var') . '/debug';
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    protected function logfileExists($filename)
    {
        $iterator = $this->getLogFileIterator();
        return $iterator->name(basename($filename))->count() == 1;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function askLogFile(InputInterface $input, OutputInterface $output)
    {
        $logFiles = $this->getLogFileIterator();
        $files = [];
        $choices = [];

        $i = 0;
        foreach ($logFiles as $logFile) {
            $files[$i++] = $logFile->getPathname();
            $choices[] = '<comment>[' . ($i) . ']</comment> ' . $logFile->getFilename() . PHP_EOL;
        }

        if ($i === 0) {
            return '';
        }

        /* @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');
        $questionObj = new ChoiceQuestion('<question>Please select a log file:</question> ', $choices);
        $questionObj->setValidator(function ($typeInput) use ($files) {
            if (!isset($files[$typeInput - 1])) {
                throw new InvalidArgumentException('Invalid file');
            }

            return $files[$typeInput - 1];
        });

        return $dialog->ask($input, $output, $questionObj);
    }
}
