<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use RuntimeException;
use WpOrg\Requests\Requests;

/**
 * Class InstallComposer
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class InstallComposer extends AbstractSubCommand
{
    public const EXEC_STATUS_OK = 0;

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $composerBin = null;
        if (OperatingSystem::isProgramInstalled('composer.phar')) {
            $composerBin = 'composer.phar';
        } elseif (OperatingSystem::isProgramInstalled('composer')) {
            $composerBin = 'composer';
        }

        if (is_null($composerBin)) {
            $composerBin = $this->downloadComposer();
        }

        if ($composerBin === '' || $composerBin === '0') {
            throw new Exception('Cannot find or install composer. Please try it manually. https://getcomposer.org/');
        }

        $this->output->writeln('<info>Found executable <comment>' . $composerBin . '</comment></info>');
        $this->config['composer_bin'] = [$composerBin];

        $composerUseSamePhpBinary = $this->hasFlagOrOptionalBoolOption('composer-use-same-php-binary', false);
        if ($composerUseSamePhpBinary) {
            $this->config['composer_bin'] = [
                OperatingSystem::getPhpBinary(),
                OperatingSystem::isProgramInstalled($composerBin),
            ];
        }

        $this->config['composer_major_version'] = $this->getMajorComposerVersion();
    }

    /**
     * @throws Exception
     */
    protected function downloadComposer(): string
    {
        $this->output->writeln('<info>Could not find composer. Try to download it.</info>');

        $response = Requests::get('https://getcomposer.org/installer');
        if (!$response->success) {
            throw new RuntimeException('Cannot download Composer installer: ' . $response->status_code);
        }

        $composerInstaller = $response->body;

        $tempComposerInstaller = $this->config['initialFolder'] . '/_composer_installer.php';
        file_put_contents($tempComposerInstaller, $composerInstaller);

        $composerInstallerOptions = '--force --install-dir=' . $this->config['initialFolder'];

        if (OperatingSystem::isWindows()) {
            $installCommand = 'php ' . $tempComposerInstaller . ' ' . $composerInstallerOptions;
        } else {
            $installCommand = '/usr/bin/env php ' . $tempComposerInstaller . ' ' . $composerInstallerOptions;
        }

        $this->output->writeln('<comment>' . $installCommand . '</comment>');
        exec($installCommand, $installationOutput, $returnStatus);
        unlink($tempComposerInstaller);
        $installationOutput = implode(PHP_EOL, $installationOutput);
        if ($returnStatus !== self::EXEC_STATUS_OK) {
            throw new Exception('Installation failed.' . $installationOutput);
        }

        $this->output->writeln('<info>Successfully installed composer to Magento root</info>');

        return $this->config['initialFolder'] . '/composer.phar';
    }

    /**
     * Composer 1 or Composer 2
     *
     * @throws Exception
     */
    protected function getMajorComposerVersion(): int
    {
        Exec::run(implode(' ', array_merge($this->config['composer_bin'], [' --version'])), $output);
        if (in_array(preg_match('#(\d+)\.(\d+)\.(\d+)#', $output, $matches), [0, false], true)) {
            throw new Exception('Could not detect a valid Composer version');
        }

        return (int) $matches[1];
    }
}
