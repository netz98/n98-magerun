<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;

/**
 * Class RewriteHtaccessFile
 * @package N98\Magento\Command\Installer\SubCommand
 */
class RewriteHtaccessFile extends AbstractSubCommand
{
    /**
     * @return void
     */
    public function execute()
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

    protected function replaceHtaccessFile()
    {
        $installationArgs = $this->config->getArray('installation_args');
        $baseUrl = $installationArgs['base-url'];
        $htaccessFile = $this->config->getString('installationFolder') . '/pub/.htaccess';

        $this->_backupOriginalFile($htaccessFile);
        $this->_replaceContent($htaccessFile, $baseUrl);
    }

    protected function _backupOriginalFile($htaccesFile)
    {
        copy(
            $htaccesFile,
            $htaccesFile . '.dist'
        );
    }

    /**
     * @param string $htaccessFile
     * @param string $baseUrl
     */
    protected function _replaceContent($htaccessFile, $baseUrl)
    {
        $content = file_get_contents($htaccessFile);
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($htaccessFile, $content);
    }
}
