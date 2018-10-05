<?php

namespace N98\Magento\Command;

use Composer\Downloader\FilesystemException;
use Composer\IO\ConsoleIO;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Christian Münch <c.muench@netz98.de>
 */
class SelfUpdateCommand extends AbstractMagentoCommand
{
    const VERSION_TXT_URL_UNSTABLE = 'https://raw.githubusercontent.com/netz98/n98-magerun/develop/version.txt';
    const MAGERUN_DOWNLOAD_URL_UNSTABLE = 'https://files.magerun.net/n98-magerun-dev.phar';
    const VERSION_TXT_URL_STABLE = 'https://raw.githubusercontent.com/netz98/n98-magerun/master/version.txt';
    const MAGERUN_DOWNLOAD_URL_STABLE = 'https://files.magerun.net/n98-magerun.phar';
    const CHANGELOG_DOWNLOAD_URL_UNSTABLE = 'https://raw.github.com/netz98/n98-magerun/develop/CHANGELOG.md';
    const CHANGELOG_DOWNLOAD_URL_STABLE = 'https://raw.github.com/netz98/n98-magerun/master/CHANGELOG.md';

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->addOption('unstable', null, InputOption::VALUE_NONE, 'Load unstable version from develop branch')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tests if there is a new version without any update.')
            ->setDescription('Updates n98-magerun.phar to the latest version.')
            ->setHelp(
                <<<EOT
The <info>self-update</info> command checks github for newer
versions of n98-magerun and if found, installs the latest.

<info>php n98-magerun.phar self-update</info>

EOT
            )
        ;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isPharMode();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     * @throws \Composer\Downloader\FilesystemException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = $input->getOption('dry-run');
        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $tempFilename = dirname($localFilename) . '/' . basename($localFilename, '.phar') . '-temp.phar';

        // check for permissions in local filesystem before start connection process
        if (!is_writable($tempDirectory = dirname($tempFilename))) {
            throw new FilesystemException(
                'n98-magerun update failed: the "' . $tempDirectory .
                '" directory used to download the temp file could not be written'
            );
        }

        if (!is_writable($localFilename)) {
            throw new FilesystemException(
                'n98-magerun update failed: the "' . $localFilename . '" file could not be written'
            );
        }

        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $rfs = new RemoteFilesystem($io);

        $loadUnstable = $input->getOption('unstable');
        if ($loadUnstable) {
            $versionTxtUrl = self::VERSION_TXT_URL_UNSTABLE;
            $remoteFilename = self::MAGERUN_DOWNLOAD_URL_UNSTABLE;
        } else {
            $versionTxtUrl = self::VERSION_TXT_URL_STABLE;
            $remoteFilename = self::MAGERUN_DOWNLOAD_URL_STABLE;
        }

        $latest = trim($rfs->getContents('raw.githubusercontent.com', $versionTxtUrl, false));

        if ($this->isOutdatedVersion($latest, $loadUnstable)) {
            $output->writeln(sprintf("Updating to version <info>%s</info>.", $latest));

            try {
                if (!$isDryRun) {
                    $this->downloadNewVersion($output, $rfs, $remoteFilename, $tempFilename);
                    $this->checkNewPharFile($tempFilename, $localFilename);
                }

                $output->writeln('<info>Successfully updated n98-magerun</info>');
                $this->showChangelog($output, $loadUnstable, $rfs);

                $this->_exit(0);
            } catch (\Exception $e) {
                @unlink($tempFilename);
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                $output->writeln('<error>The download is corrupted (' . $e->getMessage() . ').</error>');
                $output->writeln('<error>Please re-run the self-update command to try again.</error>');
            }
        } else {
            $output->writeln("<info>You are using the latest n98-magerun version.</info>");
        }
    }

    /**
     * Stop execution
     *
     * This is a workaround to prevent warning of dispatcher after replacing
     * the phar file.
     *
     * @param int $statusCode
     * @return void
     */
    protected function _exit($statusCode = 0)
    {
        exit($statusCode);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $rfs
     * @param $remoteFilename
     * @param $tempFilename
     */
    private function downloadNewVersion(OutputInterface $output, $rfs, $remoteFilename, $tempFilename)
    {
        $rfs->copy('raw.github.com', $remoteFilename, $tempFilename);

        if (!file_exists($tempFilename)) {
            $output->writeln('<error>The download of the new n98-magerun version failed for an unexpected reason');
        }
    }

    /**
     * @param $tempFilename
     * @param $localFilename
     */
    private function checkNewPharFile($tempFilename, $localFilename)
    {
        \error_reporting(E_ALL); // supress notices

        @chmod($tempFilename, 0777 & ~umask());
        // test the phar validity
        $phar = new \Phar($tempFilename);
        // free the variable to unlock the file
        unset($phar);
        @rename($tempFilename, $localFilename);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $loadUnstable
     * @param $rfs
     */
    private function showChangelog(OutputInterface $output, $loadUnstable, $rfs)
    {
        if ($loadUnstable) {
            $changeLogContent = $rfs->getContents(
                'raw.github.com',
                self::CHANGELOG_DOWNLOAD_URL_UNSTABLE,
                false
            );
        } else {
            $changeLogContent = $rfs->getContents(
                'raw.github.com',
                self::CHANGELOG_DOWNLOAD_URL_STABLE,
                false
            );
        }

        if ($changeLogContent) {
            $output->writeln($changeLogContent);
        }

        if ($loadUnstable) {
            $unstableFooterMessage = <<<UNSTABLE_FOOTER
<comment>
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!! DEVELOPMENT VERSION. DO NOT USE IN PRODUCTION !!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
</comment>
UNSTABLE_FOOTER;
            $output->writeln($unstableFooterMessage);
        }
    }

    /**
     * @param $latest
     * @param $loadUnstable
     * @return bool
     */
    private function isOutdatedVersion($latest, $loadUnstable)
    {
        return $this->getApplication()->getVersion() !== $latest || $loadUnstable;
    }
}
