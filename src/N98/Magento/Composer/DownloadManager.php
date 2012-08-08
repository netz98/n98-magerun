<?php

namespace N98\Magento\Composer;

use Composer\IO\NullIO;
use Composer\IO\IOInterface;
use Composer\Downloader\DownloadManager as BaseDownloadManager;
use Composer\Downloader as ComposerDownloader;

class DownloadManager extends BaseDownloadManager
{
    public function __construct()
    {
        $io = new NullIO();
        parent::__construct(true);
        $this->registerStandardDownloaders($io);
    }

    protected function registerStandardDownloaders(IOInterface $io)
    {
        $this->setDownloader('git', new ComposerDownloader\GitDownloader($io));
        $this->setDownloader('svn', new ComposerDownloader\SvnDownloader($io));
        $this->setDownloader('hg', new ComposerDownloader\HgDownloader($io));
        $this->setDownloader('zip', new ComposerDownloader\ZipDownloader($io));
        $this->setDownloader('tar', new ComposerDownloader\TarDownloader($io));
        $this->setDownloader('phar', new ComposerDownloader\PharDownloader($io));
        $this->setDownloader('file', new ComposerDownloader\FileDownloader($io));
    }
}
