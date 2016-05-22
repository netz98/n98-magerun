<?php

namespace N98\Magento\Command\Media;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

class DumpCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('media:dump')
            ->addOption('strip', '', InputOption::VALUE_NONE, 'Excludes image cache')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->setDescription('Creates an archive with content of media folder.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandConfig = $this->getCommandConfig();

        $this->detectMagento($output);
        $finder = new Finder();
        $finder
            ->files()
            ->followLinks(true)
            ->in($this->getApplication()->getMagentoRootFolder() . DIRECTORY_SEPARATOR . 'media');
        if ($input->getOption('strip')) {
            $finder->exclude($commandConfig['strip']['folders']);
        }

        $filename = (string) $input->getArgument('filename');
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
            /* @var $file SplFileInfo */
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
    }
}
