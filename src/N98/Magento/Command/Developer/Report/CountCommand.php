<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Report;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Count reports command
 *
 * @package N98\Magento\Command\Developer\Report
 */
class CountCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:report:count';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Get count of report files.';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $dir = Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR;
        $count = (string) $this->getFileCount($dir);

        $output->writeln($count);

        return Command::SUCCESS;
    }

    /**
     * Returns the number of files in the directory.
     *
     * @param string $path Path to the directory
     * @return int
     */
    protected function getFileCount(string $path): int
    {
        $finder = Finder::create();
        return $finder->files()->ignoreUnreadableDirs()->in($path)->count();
    }
}
