<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractRepositoryCommand
 *
 * @package N98\Magento\Command\Script\Repository
 */
class AbstractRepositoryCommand extends AbstractMagentoCommand
{
    /**
     * Extension of n98-magerun scripts
     */
    public const MAGERUN_EXTENSION = '.magerun';

    /**
     * @return array
     */
    protected function getScripts()
    {
        $folders = (array) $this->getApplication()->getConfig('script', 'folders');
        $magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $scriptLoader = new ScriptLoader($folders, $magentoRootFolder);

        return $scriptLoader->getFiles();
    }
}
