<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Finder\Finder;

/**
 * Class SetDirectoryPermissions
 * @package N98\Magento\Command\Installer\SubCommand
 */
class SetDirectoryPermissions extends AbstractSubCommand
{
    /**
     * @return void
     */
    public function execute()
    {
        try {
            $installationFolder = $this->config->getString('installationFolder');

            $varFolder = $installationFolder . '/var';
            if (!is_dir($varFolder)) {
                @mkdir($varFolder);
            }
            @chmod($varFolder, 0777);

            $varCacheFolder = $installationFolder . '/var/cache';
            if (!is_dir($varCacheFolder)) {
                @mkdir($varCacheFolder);
            }
            @chmod($varCacheFolder, 0777);

            $mediaFolder = $installationFolder . '/media';
            if (!is_dir($mediaFolder)) {
                @mkdir($mediaFolder);
            }
            @chmod($mediaFolder, 0777);

            $finder = Finder::create();
            $finder->directories()
                ->ignoreUnreadableDirs(true)
                ->in([$varFolder, $mediaFolder]);
            foreach ($finder as $dir) {
                @chmod($dir->getRealpath(), 0777);
            }
        } catch (\Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
