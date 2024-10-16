<?php

namespace N98\Magento\Command\Developer\Theme;

use DateTime;
use N98\JUnitXml\Document as JUnitXmlDocument;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Find duplicate theme command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
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
            ->addOption(
                'log-junit',
                null,
                InputOption::VALUE_REQUIRED,
                'Log duplicates in JUnit XML format to defined file.'
            )
            ->setDescription('Find duplicate files (templates, layout, locale, etc.) between two themes.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $time = microtime(true);
        $this->detectMagento($output);

        $referenceFiles = $this->getChecksums(
            $this->_magentoRootFolder . '/app/design/frontend/' . $input->getArgument('originalTheme')
        );

        $themeFolder = $this->_magentoRootFolder . '/app/design/frontend/' . $input->getArgument('theme');
        $themeFiles = $this->getChecksums($themeFolder);

        $duplicates = [];
        foreach ($themeFiles as $themeFilename => $themeFileChecksum) {
            if (isset($referenceFiles[$themeFilename])
                && $themeFileChecksum == $referenceFiles[$themeFilename]
            ) {
                $duplicates[] = $themeFolder . '/' . $themeFilename;
            }
        }

        if ($input->getOption('log-junit')) {
            $this->logJUnit($input, $duplicates, $input->getOption('log-junit'), microtime($time) - $time);
        } else {
            if (count($duplicates) === 0) {
                $output->writeln('<info>No duplicates were found</info>');
            } else {
                $output->writeln($duplicates);
            }
        }

        return 0;
    }

    /**
     * @param string $baseFolder
     * @return array
     */
    protected function getChecksums($baseFolder)
    {
        $finder = Finder::create();
        $finder
            ->files()
            ->ignoreUnreadableDirs(true)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->followLinks()
            ->in($baseFolder);
        $checksums = [];
        foreach ($finder as $file) {
            /* @var \Symfony\Component\Finder\SplFileInfo $file */
            if (file_exists($file->getRealPath())) {
                $checksums[$file->getRelativePathname()] = md5_file($file->getRealPath());
            }
        }

        return $checksums;
    }

    /**
     * @param InputInterface $input
     * @param array          $duplicates
     * @param string         $filename
     * @param float          $duration
     */
    protected function logJUnit($input, array $duplicates, $filename, $duration)
    {
        $document = new JUnitXmlDocument();
        $suite = $document->addTestSuite();
        $suite->setName('n98-magerun: ' . $this->getName());
        $suite->setTimestamp(new DateTime());
        $suite->setTime($duration);

        $testCase = $suite->addTestCase();
        $testCase->setName(
            'Magento Duplicate Theme Files: ' . $input->getArgument('theme') . ' | ' .
            $input->getArgument('originalTheme')
        );
        $testCase->setClassname('ConflictsCommand');
        foreach ($duplicates as $duplicate) {
            $testCase->addFailure(
                sprintf('Duplicate File: %s', $duplicate),
                'MagentoThemeDuplicateFileException'
            );
        }

        $document->save($filename);
    }
}
