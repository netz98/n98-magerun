<?php

namespace N98\Magento\Command\Developer\Report;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class CountCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:report:count')
            ->setDescription('Get count of report files');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $this->initMagento();

        $dir = \Mage::getBaseDir('var') . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR;
        $count = $this->getFileCount($dir);

        $output->writeln($count);
    }

    /**
     * Returns the number of files in the directory.
     *
     * @param string $path Path to the directory
     * @return int
     */
    protected function getFileCount($path)
    {
        $finder = Finder::create();
        return $finder->files()->ignoreUnreadableDirs(true)->in($path)->count();
    }
}
