<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Abstract repository class
 *
 * @package N98\Magento\Command\Script\Repository
 */
class AbstractRepositoryCommand extends AbstractCommand
{
    /**
     * Extension of n98-magerun scripts
     */
    public const MAGERUN_EXTENSION = '.magerun';

    /**
     * @return array<string, array{fileinfo: SplFileInfo, description: string, location: string}>
     */
    protected function getScripts(): array
    {
        $folders = (array) $this->getApplication()->getConfig('script', 'folders');
        $magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $loader = new ScriptLoader($folders, $magentoRootFolder);

        return $loader->getFiles();
    }
}
