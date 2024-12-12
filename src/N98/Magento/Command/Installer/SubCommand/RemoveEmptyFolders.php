<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class RemoveEmptyFolders
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class RemoveEmptyFolders extends AbstractSubCommand
{
    public function execute(): void
    {
        if (is_dir(getcwd() . '/vendor')) {
            $finder = new Finder();
            $finder->files()->depth(3)->in(getcwd() . '/vendor');
            if ($finder->count() == 0) {
                $filesystem = new Filesystem();
                $filesystem->recursiveRemoveDirectory(getcwd() . '/vendor');
            }
        }
    }
}
