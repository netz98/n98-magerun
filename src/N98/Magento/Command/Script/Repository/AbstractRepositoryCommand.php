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
        $config = $this->getApplication()->getConfig();
        $loader = new ScriptLoader($config['script']['folders'], $this->getApplication()->getMagentoRootFolder());
        $files = $loader->getFiles();

        return $files;
    }
}
