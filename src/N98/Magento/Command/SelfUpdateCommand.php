<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Exception;
use N98\Util\Markdown\VersionFilePrinter;
use Phar;
use PharException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;
use WpOrg\Requests\Hooks;
use WpOrg\Requests\Requests;

/**
 * Self-update command
 *
 * @package N98\Magento\Command
 *
 * @codeCoverageIgnore
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Christian Münch <c.muench@netz98.de>
 */
class SelfUpdateCommand extends AbstractMagentoCommand
{
    public const VERSION_TXT_URL_UNSTABLE = 'https://raw.githubusercontent.com/netz98/n98-magerun/develop/version.txt';

    public const MAGERUN_DOWNLOAD_URL_UNSTABLE = 'https://files.magerun.net/n98-magerun-dev.phar';

    public const VERSION_TXT_URL_STABLE = 'https://raw.githubusercontent.com/netz98/n98-magerun/master/version.txt';

    public const MAGERUN_DOWNLOAD_URL_STABLE = 'https://files.magerun.net/n98-magerun.phar';

    public const CHANGELOG_DOWNLOAD_URL_UNSTABLE = 'https://raw.github.com/netz98/n98-magerun/develop/CHANGELOG.md';

    public const CHANGELOG_DOWNLOAD_URL_STABLE = 'https://raw.github.com/netz98/n98-magerun/master/CHANGELOG.md';

    protected function configure(): void
    {
        $this
            ->setName('self-update')
            ->setAliases(['selfupdate'])
            ->addOption('unstable', null, InputOption::VALUE_NONE, 'Load unstable version from develop branch')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tests if there is a new version without any update.')
            ->setDescription('Updates n98-magerun2.phar to the latest version.');
    }

    public function getHelp(): string
    {
        return <<<HELP
The <info>self-update</info> command checks GitHub for newer
versions of n98-magerun and if found, installs the latest.

<info>php n98-magerun.phar self-update</info>

HELP;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');

        if (!$this->getApplication()->isPharMode()) {
            $isDryRun = true;
            $output->writeln('<warning>Self-update is supported only for phar files.</warning>');
            $output->writeln('Dry-run mode is enabled.');
        }

        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $tempFilename = dirname($localFilename) . '/' . basename($localFilename, '.phar') . '-temp.phar';

        // check for permissions in local filesystem before start connection process
        if (!is_writable($tempDirectory = dirname($tempFilename))) {
            throw new RuntimeException(
                'n98-magerun2 update failed: the "' . $tempDirectory .
                '" directory used to download the temp file could not be written',
            );
        }

        if (!is_writable($localFilename)) {
            throw new RuntimeException(
                'n98-magerun2 update failed: the "' . $localFilename . '" file could not be written',
            );
        }

        $loadUnstable = $input->getOption('unstable');
        if ($loadUnstable) {
            $versionTxtUrl = self::VERSION_TXT_URL_UNSTABLE;
            $remotePharDownloadUrl = self::MAGERUN_DOWNLOAD_URL_UNSTABLE;
        } else {
            $versionTxtUrl = self::VERSION_TXT_URL_STABLE;
            $remotePharDownloadUrl = self::MAGERUN_DOWNLOAD_URL_STABLE;
        }

        $response = Requests::get(
            $versionTxtUrl,
            [],
            [
                'verify' => true,
            ],
        );

        if (!$response->success) {
            throw new RuntimeException('Cannot get version: ' . $response->status_code);
        }

        $latestVersion = trim($response->body);

        if ($this->isOutdatedVersion($latestVersion, $loadUnstable)) {
            $output->writeln(sprintf('Updating to version <info>%s</info>.', $latestVersion));

            try {
                $this->downloadNewPhar($output, $remotePharDownloadUrl, $tempFilename);
                $this->checkNewPharFile($tempFilename);

                $changelog = $this->getChangelog($loadUnstable);

                if (!$isDryRun) {
                    $this->replaceExistingPharFile($tempFilename, $localFilename);
                }

                $output->writeln('');
                $output->writeln('');
                $output->writeln($changelog);
                $output->writeln('<info>---------------------------------</info>');
                $output->writeln('<info>Successfully updated n98-magerun2</info>');
                $output->writeln('<info>---------------------------------</info>');

                $this->_exit(0);
            } catch (Exception $exception) {
                @unlink($tempFilename);
                if (!$exception instanceof UnexpectedValueException && !$exception instanceof PharException) {
                    throw $exception;
                }

                $output->writeln('<error>The download is corrupted (' . $exception->getMessage() . ').</error>');
                $output->writeln('<error>Please re-run the self-update command to try again.</error>');
            }
        } else {
            $output->writeln('<info>You are using the latest n98-magerun2 version.</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * Stop execution
     *
     * This is a workaround to prevent warning of dispatcher after replacing
     * the phar file.
     */
    protected function _exit(int $statusCode = 0): void
    {
        exit($statusCode);
    }

    private function downloadNewPhar(OutputInterface $output, string $remoteUrl, string $tempFilename): void
    {
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('[%bar%] %current% of %max% bytes downloaded');

        $hooks = new Hooks();

        $response = Requests::head(
            $remoteUrl,
            [],
            [
                'verify' => true,
                'headers' => [
                    'Accept-Encoding' => 'deflate, gzip, br, zstd',
                ],
            ],
        );

        if (!$response->success) {
            throw new RuntimeException('Cannot download phar file: ' . $response->status_code);
        }

        $filesize = $response->headers['content-length'];

        $hooks->register('curl.after_request', function (&$headers, &$info) use (&$filesize): void {
            $filesize = $info['size_download'];
        });

        $progressBar->setMaxSteps((int) $filesize);

        $hooks->register(
            'request.progress',
            function ($data, $responseBytes, $responseByteLimit) use ($progressBar): void {
                $progressBar->setProgress($responseBytes);
            },
        );

        $response = Requests::get(
            $remoteUrl,
            [],
            [
                'blocking' => true,
                'hooks' => $hooks,
                'verify' => true,
                'headers' => [
                    'Accept-Encoding' => 'deflate, gzip, br, zstd',
                ],
            ],
        );

        if (!$response->success) {
            throw new RuntimeException('Cannot download phar file: ' . $response->status_code);
        }

        file_put_contents($tempFilename, $response->body);

        if (!file_exists($tempFilename)) {
            $output->writeln('<error>The download of the new n98-magerun2 version failed for an unexpected reason');
        }
    }

    private function checkNewPharFile(string $tempFilename): void
    {
        error_reporting(E_ALL); // suppress notices

        @chmod($tempFilename, 0777 & ~umask());
        // test the phar validity
        $phar = new Phar($tempFilename);
        // free the variable to unlock the file
        unset($phar);
    }

    private function replaceExistingPharFile(string $tempFilename, string $localFilename): void
    {
        if (!@rename($tempFilename, $localFilename)) {
            throw new RuntimeException(
                sprintf('Cannot replace existing phar file "%s". Please check permissions.', $localFilename),
            );
        }
    }

    /**
     * Download changelog
     */
    private function getChangelog(bool $loadUnstable): string
    {
        $changelog = '';

        $changeLogUrl = $loadUnstable ? self::CHANGELOG_DOWNLOAD_URL_UNSTABLE : self::CHANGELOG_DOWNLOAD_URL_STABLE;

        $response = Requests::get(
            $changeLogUrl,
            [],
            [
                'verify' => true,
                'headers' => [
                    'Accept-Encoding' => 'deflate, gzip, br, zstd',
                ],
            ],
        );

        if (!$response->success) {
            throw new RuntimeException('Cannot download changelog: ' . $response->status_code);
        }

        $changeLogContent = $response->body;
        if ($changeLogContent) {
            $versionFilePrinter = new VersionFilePrinter($changeLogContent);
            $previousVersion = $this->getApplication()->getVersion();
            $changelog .= $versionFilePrinter->printFromVersion($previousVersion) . "\n";
        }

        if ($loadUnstable) {
            $unstableFooterMessage = <<<UNSTABLE_FOOTER
<comment>
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
!! DEVELOPMENT VERSION. DO NOT USE IN PRODUCTION !!
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
</comment>
UNSTABLE_FOOTER;

            $changelog .= $unstableFooterMessage . "\n";
        }

        return $changelog;
    }

    private function isOutdatedVersion(string $latest, bool $loadUnstable): bool
    {
        if ($this->getApplication()->getVersion() !== $latest) {
            return true;
        }

        return $loadUnstable;
    }
}
