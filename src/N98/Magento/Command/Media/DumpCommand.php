<?php

declare(strict_types=1);

namespace N98\Magento\Command\Media;

use Carbon\Carbon;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('media:dump')
            ->addOption('strip', '', InputOption::VALUE_NONE, 'Excludes image cache')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->setDescription('Creates an archive with content of media folder.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandConfig = $this->getCommandConfig();

        $this->detectMagento($output);
        $finder = new Finder();
        $finder
            ->files()->followLinks()
            ->in($this->getApplication()->getMagentoRootFolder() . DIRECTORY_SEPARATOR . 'media');
        if ($input->getOption('strip')) {
            $finder->exclude($commandConfig['strip']['folders']);
        }

        $filename = (string) $input->getArgument('filename');
        if (is_dir($filename)) { // support for dot dir
            $filename = realpath($filename);
            $filename .= '/';
        }

        if ($filename === '' || $filename === '0' || is_dir($filename)) {
            $filename .= 'media_' . Carbon::now()->format('Ymd_his') . '.zip';
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($filename, ZIPARCHIVE::CREATE);
        $zipArchive->addEmptyDir('media');

        $lastFolder = '';
        foreach ($finder as $file) {
            /* @var SplFileInfo $file */
            $currentFolder = pathinfo($file->getRelativePathname(), PATHINFO_DIRNAME);
            if ($currentFolder !== $lastFolder) {
                $output->writeln(
                    sprintf('<info>Compress directory:</info> <comment>media/%s</comment>', $currentFolder),
                );
            }

            $zipArchive->addFile($file->getPathname(), 'media' . DIRECTORY_SEPARATOR . $file->getRelativePathname());

            $lastFolder = $currentFolder;
        }

        $zipArchive->close();

        return Command::SUCCESS;
    }
}
