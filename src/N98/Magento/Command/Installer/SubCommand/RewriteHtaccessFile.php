<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;

/**
 * Class RewriteHtaccessFile
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class RewriteHtaccessFile extends AbstractSubCommand
{
    public function execute(): void
    {
        if ($this->hasFlagOrOptionalBoolOption('useDefaultConfigParams')) {
            return;
        }

        $this->getCommand()->getApplication()->setAutoExit(false);

        $flag = $this->getOptionalBooleanOption('replaceHtaccessFile', 'Write BaseURL to .htaccess file?', false);

        if ($flag) {
            $this->replaceHtaccessFile();
        }
    }

    protected function replaceHtaccessFile(): void
    {
        $installationArgs = $this->config->getArray('installation_args');
        $baseUrl = $installationArgs['base-url'];
        $htaccessFile = $this->config->getString('installationFolder') . '/pub/.htaccess';

        $this->_backupOriginalFile($htaccessFile);
        $this->_replaceContent($htaccessFile, $baseUrl);
    }

    protected function _backupOriginalFile(string $htaccessFile): void
    {
        copy(
            $htaccessFile,
            $htaccessFile . '.dist',
        );
    }

    protected function _replaceContent(string $htaccessFile, string $baseUrl): void
    {
        $content = (string) file_get_contents($htaccessFile);
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($htaccessFile, $content);
    }
}
