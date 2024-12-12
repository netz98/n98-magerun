<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Report;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Count report command
 *
 * @package N98\Magento\Command\Developer\Report
 */
class CountCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:report:count')
            ->setDescription('Get count of report files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $dir = Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR;
        $count = $this->getFileCount($dir);

        $output->writeln((string) $count);
        return Command::SUCCESS;
    }

    /**
     * Returns the number of files in the directory.
     */
    protected function getFileCount(string $path): int
    {
        $finder = Finder::create();
        return $finder->files()->ignoreUnreadableDirs(true)->in($path)->count();
    }
}
