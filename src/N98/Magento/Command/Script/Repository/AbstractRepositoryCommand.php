<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractMagentoCommand;

class AbstractRepositoryCommand extends AbstractMagentoCommand
{
    /**
     * @return array
     */
    protected function getScripts()
    {
        $config = $this->getApplication()->getConfig();
        $loader = new ScriptLoader($config['script']['folders']);
        $files = $loader->getFiles();

        return $files;
    }
}