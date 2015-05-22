<?php

namespace N98\Magento\Command;

use Composer\Downloader\FilesystemException;
use Composer\IO\ConsoleIO;
use Composer\Util\RemoteFilesystem;
use N98\Magento\Command\AbstractMagentoCommand;
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
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->addOption('unstable', null, InputOption::VALUE_NONE, 'Load unstable version from develop branch')
            ->setDescription('Updates n98-magerun.phar to the latest version.')
            ->setHelp(<<<EOT
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $tempFilename = dirname($localFilename) . '/' . basename($localFilename, '.phar').'-temp.phar';

        // check for permissions in local filesystem before start connection process
        if (!is_writable($tempDirectory = dirname($tempFilename))) {
            throw new FilesystemException('n98-magerun update failed: the "' . $tempDirectory . '" directory used to download the temp file could not be written');
        }

        if (!is_writable($localFilename)) {
            throw new FilesystemException('n98-magerun update failed: the "' . $localFilename . '" file could not be written');
        }

        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $rfs = new RemoteFilesystem($io);

        $loadUnstable = $input->getOption('unstable');
        if ($loadUnstable) {
            $versionTxtUrl = 'https://raw.githubusercontent.com/netz98/n98-magerun/develop/version.txt';
            $remoteFilename = 'http://files.magerun.net/n98-magerun-dev.phar';
        } else {
            $versionTxtUrl = 'https://raw.githubusercontent.com/netz98/n98-magerun/master/version.txt';
            $remoteFilename = 'http://files.magerun.net/n98-magerun-latest.phar';
        }

        $latest = trim($rfs->getContents('raw.githubusercontent.com', $versionTxtUrl, false));

        if ($this->getApplication()->getVersion() !== $latest || $loadUnstable) {
            $output->writeln(sprintf("Updating to version <info>%s</info>.", $latest));

            $rfs->copy('raw.github.com', $remoteFilename, $tempFilename);

            if (!file_exists($tempFilename)) {
                $output->writeln('<error>The download of the new n98-magerun version failed for an unexpected reason');

                return 1;
            }

            try {
                \error_reporting(E_ALL); // supress notices

                @chmod($tempFilename, 0777 & ~umask());
                // test the phar validity
                $phar = new \Phar($tempFilename);
                // free the variable to unlock the file
                unset($phar);
                @rename($tempFilename, $localFilename);
                $output->writeln('<info>Successfully updated n98-magerun</info>');

                if ($loadUnstable) {
                    $changeLogContent = $rfs->getContents(
                        'raw.github.com',
                        'https://raw.github.com/netz98/n98-magerun/develop/changes.txt',
                        false
                    );
                } else {
                    $changeLogContent = $rfs->getContents(
                        'raw.github.com',
                        'https://raw.github.com/netz98/n98-magerun/master/changes.txt',
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

                $this->_exit();
            } catch (\Exception $e) {
                @unlink($tempFilename);
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                $output->writeln('<error>The download is corrupted ('.$e->getMessage().').</error>');
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
     * @return void
     */
    protected function _exit()
    {
        exit;
    }
}
