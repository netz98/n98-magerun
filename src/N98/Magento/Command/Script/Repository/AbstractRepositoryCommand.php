<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractRepositoryCommand extends AbstractMagentoCommand
{
    /**
     * Extension of n98-magerun scripts
     */
    const MAGERUN_EXTENSION = '.magerun';

    /**
     * @return array
     */
    protected function getScripts()
    {
        $folders = (array) $this->getApplication()->getConfig('script', 'folders');
        $magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $loader = new ScriptLoader($folders, $magentoRootFolder);
        $files = $loader->getFiles();

        return $files;
    }
}
