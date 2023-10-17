<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media;

use N98\Magento\Application;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

/**
 * Dump media command
 *
 * @package N98\Magento\Command\Media
 */
class DumpCommand extends AbstractMagentoCommand
{
    private const COMMAND_ARGUMENT_FILENAME = 'filename';
    private const COMMAND_OPTION_STRIP = 'strip';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'media:dump';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Creates an archive with content of media folder';

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_STRIP,
                '',
                InputOption::VALUE_NONE,
                'Excludes image cache'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_FILENAME,
                InputArgument::OPTIONAL,
                'Dump filename'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        /** @var Application $app */
        $app = $this->getApplication();
        if (!$app) {
            throw new RuntimeException('Could not run application.');
        }

        $rootFolder = $app->getMagentoRootFolder();
        if (!$rootFolder) {
            throw new RuntimeException('Could not find root folder.');
        }

        $commandConfig = $this->getCommandConfig();

        $finder = new Finder();
        $finder
            ->files()->followLinks()
            ->in($rootFolder . DIRECTORY_SEPARATOR . 'media');

        if ($input->getOption(self::COMMAND_OPTION_STRIP)) {
            $finder->exclude($commandConfig['strip']['folders']);
        }

        $filename = $this->getArgumentString($input, self::COMMAND_ARGUMENT_FILENAME);
        if (is_dir($filename)) { // support for dot dir
            $filename = realpath($filename);
            $filename .= '/';
        }
        if (empty($filename) || is_dir($filename)) {
            $filename .= 'media_' . date('Ymd_his') . '.zip';
        }

        $zip = new ZipArchive();
        $zip->open($filename, ZIPARCHIVE::CREATE);
        $zip->addEmptyDir('media');
        $lastFolder = '';
        foreach ($finder as $file) {
            /* @var SplFileInfo $file */
            $currentFolder = pathinfo($file->getRelativePathname(), PATHINFO_DIRNAME);
            if ($currentFolder != $lastFolder) {
                $output->writeln(
                    sprintf('<info>Compress directory:</info> <comment>media/%s</comment>', $currentFolder)
                );
            }
            $zip->addFile($file->getPathname(), 'media' . DIRECTORY_SEPARATOR . $file->getRelativePathname());

            $lastFolder = $currentFolder;
        }

        $zip->close();

        return Command::SUCCESS;
    }
}
