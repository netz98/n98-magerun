<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

/**
 * Class AbstractLogCommand
 *
 * @package N98\Magento\Command\Developer\Log
 */
class AbstractLogCommand extends AbstractMagentoCommand
{
    /**
     * @return Finder
     */
    protected function getLogFileIterator(): Finder
    {
        $finder = Finder::create();
        $finder->ignoreUnreadableDirs();

        $logDirs = [$this->getLogDir()];

        if (is_dir($this->getDebugDir())) {
            $logDirs[] = $this->getDebugDir();
        }

        return $finder->files()->in($logDirs);
    }

    /**
     * @return string
     */
    protected function getLogDir(): string
    {
        return Mage::getBaseDir('log');
    }

    /**
     * @return string
     */
    protected function getDebugDir(): string
    {
        return Mage::getBaseDir('var') . '/debug';
    }

    /**
     * @param string $filename
     * @return bool
     */
    protected function logfileExists(string $filename): bool
    {
        $iterator = $this->getLogFileIterator();
        return $iterator->name(basename($filename))->count() == 1;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function askLogFile(InputInterface $input, OutputInterface $output)
    {
        $logFiles = $this->getLogFileIterator();

        if (!$logFiles->count()) {
            $output->writeln('<info>No logfiles found.</info>');
            return '';
        }

        $files = [];
        $choices = [];

        foreach ($logFiles as $logFile) {
            $files[] = $logFile->getPathname();
            $choices[] = $logFile->getFilename() . PHP_EOL;
        }

        $dialog = $this->getQuestionHelper();
        $questionObj = new ChoiceQuestion('<question>Please select a log file:</question> ', $choices);
        $questionObj->setValidator(function ($typeInput) use ($files) {
            if (!isset($files[$typeInput])) {
                throw new InvalidArgumentException('Invalid file');
            }

            return $files[$typeInput];
        });

        return $dialog->ask($input, $output, $questionObj);
    }
}
