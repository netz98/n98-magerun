<?php

namespace N98\Magento\Command\Developer\Theme;

use DateTime;
use N98\JUnitXml\Document as JUnitXmlDocument;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Theme duplicates command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class DuplicatesCommand extends AbstractMagentoCommand
{
    public const COMMAND_ARGUMENT_THEME = 'theme';

    public const COMMAND_ARGUMENT_ORIG_THEME = 'originalTheme';

    public const COMMAND_OPTION_LOG_JUNIT = 'log-junit';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:theme:duplicates';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Find duplicate files (templates, layout, locale, etc.) between two themes.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_THEME,
                InputArgument::REQUIRED,
                'Your theme'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ORIG_THEME,
                InputArgument::OPTIONAL,
                'Original theme to compare. Default is "base/default"',
                'base/default'
            )
            ->addOption(
                self::COMMAND_OPTION_LOG_JUNIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Log duplicates in JUnit XML format to defined file.'
            )
        ;
    }

    /**
     * @return string
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
            $this->_magentoRootFolder . '/app/design/frontend/'
            . $input->getArgument(self::COMMAND_ARGUMENT_ORIG_THEME)
        );

        $themeFolder = $this->_magentoRootFolder . '/app/design/frontend/'
            . $input->getArgument(self::COMMAND_ARGUMENT_THEME);
        $themeFiles = $this->getChecksums($themeFolder);

        $duplicates = [];
        foreach ($themeFiles as $themeFilename => $themeFileChecksum) {
            if (isset($referenceFiles[$themeFilename])
                && $themeFileChecksum == $referenceFiles[$themeFilename]
            ) {
                $duplicates[] = $themeFolder . '/' . $themeFilename;
            }
        }

        /** @var string $filename */
        $filename = $input->getOption(self::COMMAND_OPTION_LOG_JUNIT);
        if ($input->getOption(self::COMMAND_OPTION_LOG_JUNIT)) {
            $this->logJUnit(
                $input,
                $duplicates,
                $filename,
                microtime(true) - $time
            );
        } else {
            if (count($duplicates) === 0) {
                $output->writeln('<info>No duplicates were found</info>');
            } else {
                $output->writeln($duplicates);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $baseFolder
     * @return array<string, string|false>
     */
    protected function getChecksums(string $baseFolder): array
    {
        $finder = Finder::create();
        $finder
            ->files()
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->followLinks()
            ->in($baseFolder);
        $checksums = [];
        foreach ($finder as $file) {
            /* @var SplFileInfo $file */
            if (file_exists($file->getRealPath())) {
                $checksums[$file->getRelativePathname()] = md5_file($file->getRealPath());
            }
        }

        return $checksums;
    }

    /**
     * @param InputInterface $input
     * @param array<int, string> $duplicates
     * @param string $filename
     * @param float $duration
     */
    protected function logJUnit(InputInterface $input, array $duplicates, string $filename, float $duration): void
    {
        $document = new JUnitXmlDocument();
        $suite = $document->addTestSuite();
        $suite->setName('n98-magerun: ' . $this->getName());
        $suite->setTimestamp(new DateTime());
        $suite->setTime($duration);

        $testCase = $suite->addTestCase();
        $testCase->setName(
            'Magento Duplicate Theme Files: ' . $input->getArgument(self::COMMAND_ARGUMENT_THEME) . ' | ' .
            $input->getArgument(self::COMMAND_ARGUMENT_ORIG_THEME)
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
