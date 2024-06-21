<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache\Dir;

use FilesystemIterator;
use N98\Magento\Command\AbstractCommand;
use N98\Util\Filesystem;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flush cache directory command
 *
 * @package N98\Magento\Command\Cache\Dir
 */
class FlushCommand extends AbstractCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:dir:flush';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Flush (empty) cache directory.';

    public function getHelp(): string
    {
        return <<<HELP
The default cache backend is the files cache in Magento. The default
directory of that default cache backend is the directory "var/cache"
within the Magento web-root directory (should be blocked from external
access).

The cache:dir:flush command will remove all files within that
directory. This is currently the most purist form to reset default
caching configuration in Magento.

Flushing the cache directory can help to re-initialize the whole Magento
application after it got stuck in cached configuration like a half-done
cache initialization, old config data within the files cache and similar.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $workingDirectory = getcwd();
        $magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $cacheDir = $magentoRootFolder . '/var/cache';

        $output->writeln(sprintf('<info>Flushing cache directory <comment>%s</comment></info>', $cacheDir));

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<debug>root-dir: <comment>%s</comment>', $magentoRootFolder));
            $output->writeln(sprintf('<debug>cwd: <comment>%s</comment>', $workingDirectory));
        }

        $this->emptyDirectory($output, $cacheDir);

        $output->writeln('<info>Cache directory flushed</info>');

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param string $path
     * @return bool
     */
    private function emptyDirectory(OutputInterface $output, string $path)
    {
        $errors = [];

        $dir = new FilesystemIterator($path);
        foreach ($dir as $file => $info) {
            /** @var SplFileInfo $info */
            if ($info->isDir()) {
                if ($output->isVerbose()) {
                    $output->writeln(
                        sprintf(
                            '<debug>Filesystem::recursiveRemoveDirectory() <comment>%s</comment></debug>',
                            $file
                        )
                    );
                }

                if (!isset($fs)) {
                    $fs = new Filesystem();
                }

                if (!$fs->recursiveRemoveDirectory($file)) {
                    $errors[] = $file;
                }
            } else {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<debug>unlink() <comment>%s</comment></debug>', $file));
                }

                if (!unlink($file)) {
                    $errors[] = $file;
                }
            }
        }

        if (!$errors) {
            return true;
        }

        $message = sprintf("Failed to empty directory %s, unable to remove:\n", var_export($path, true));
        foreach ($errors as $error) {
            $message .= sprintf(" - %s\n", var_export($error, true));
        }

        throw new RuntimeException($message);
    }
}
