<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache\Dir;

use FilesystemIterator;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flush cache directory command
 *
 * @package N98\Magento\Command\Cache\Dir
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class FlushCommand extends AbstractMagentoCommand
{
    private OutputInterface $output;

    public const NAME = 'cache:dir:flush';

    protected function configure(): void
    {
        $this
            ->setName(FlushCommand::NAME)
            ->setDescription('Flush (empty) Magento cache directory');
    }

    public function getHelp(): string
    {
        return <<<HELP
The default cache backend is the files cache in Magento. The default
directory of that default cache backend is the directory "var/cache"
within the Magento web-root directory (should be blocked from external
access).

The cache:dir:flush Magerun command will remove all files within that
directory. This is currently the most purist form to reset default
caching configuration in Magento.

Flushing the cache directory can help to re-initialize the whole Magento
application after it got stuck in cached configuration like a half-done
cache initialization, old config data within the files cache and similar.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->detectMagento($output);

        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $workingDirectory   = getcwd();
        $magentoRootFolder  = $this->getApplication()->getMagentoRootFolder();
        $cacheDir           = $magentoRootFolder . '/var/cache';

        $output->writeln(sprintf('<info>Flushing cache directory <comment>%s</comment></info>', $cacheDir));

        $this->verbose(sprintf('<debug>root-dir: <comment>%s</comment>', $magentoRootFolder));
        $this->verbose(sprintf('<debug>cwd: <comment>%s</comment>', $workingDirectory));

        $this->emptyDirectory($cacheDir);

        $output->writeln('Cache directory flushed');
        return Command::SUCCESS;
    }

    private function emptyDirectory(string $path): bool
    {
        $errors = [];

        $dir = new FilesystemIterator($path);
        /** @var SplFileInfo $info */
        foreach ($dir as $file => $info) {
            if ($info->isDir()) {
                $this->verbose(
                    '<debug>Filesystem::recursiveRemoveDirectory() <comment>' . $file . '</comment></debug>',
                );
                if (!isset($filesystem)) {
                    $filesystem = new Filesystem();
                }

                if (!$filesystem->recursiveRemoveDirectory($file)) {
                    $errors[] = $file;
                };
            } else {
                $this->verbose('<debug>unlink() <comment>' . $file . '</comment></debug>');
                if (!unlink($file)) {
                    $errors[] = $file;
                }
            }
        }

        if ($errors === []) {
            return true;
        }

        $message = sprintf("Failed to empty directory %s, unable to remove:\n", var_export($path, true));
        foreach ($errors as $error) {
            $message .= sprintf(" - %s\n", var_export($error, true));
        }

        throw new RuntimeException($message);
    }

    private function verbose(string $message): void
    {
        $output = $this->output;
        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln($message);
        }
    }
}
