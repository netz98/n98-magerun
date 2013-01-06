<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

class DuplicatesCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:theme:duplicates')
            ->addArgument('theme', InputArgument::REQUIRED, 'Your theme')
            ->addArgument(
                'originalTheme',
                InputArgument::OPTIONAL,
                'Original theme to comapre. Default is "base/default"',
                'base/default'
            )
            ->setDescription('Find duplicate files in your theme')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            $output->writeln('<error>Magento 2 is currently not supported.</error>');
        } else {
            $referenceFiles = $this->getChecksums(
                $this->_magentoRootFolder . '/app/design/frontend/' . $input->getArgument('originalTheme')
            );

            $themeFolder = $this->_magentoRootFolder . '/app/design/frontend/' . $input->getArgument('theme');
            $themeFiles = $this->getChecksums($themeFolder);

            $duplicates = array();
            foreach ($themeFiles as $themeFilename => $themeFileChecksum) {
                if (isset($referenceFiles[$themeFilename])
                    && $themeFileChecksum == $referenceFiles[$themeFilename]
                ) {
                    $duplicates[] = $themeFolder . '/' . $themeFilename;
                }
            }

            if (count($duplicates) === 0) {
                $output->writeln('<info>No duplicates was found</info>');
            } else {
                $output->writeln($duplicates);
            }
        }
    }

    /**
     * @param string $baseFolder
     * @return array
     */
    protected function getChecksums($baseFolder)
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->ignoreVCS(true)->followLinks()->in($baseFolder);
        $checksums = array();
        foreach ($finder as $file) {
            /* @var $file \Symfony\Component\Finder\SplFileInfo */
            if (file_exists($file->getRealPath())) {
                $checksums[$file->getRelativePathname()] = md5_file($file->getRealPath());
            }
        }

        return $checksums;
    }
}