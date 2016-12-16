<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Cache\Dir;

use FilesystemIterator;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FlushCommand
 *
 * @package N98\Magento\Command\Cache
 */
class FlushCommand extends AbstractMagentoCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    const NAME = 'cache:dir:flush';

    protected function configure()
    {
        $this
            ->setName(FlushCommand::NAME)
            ->setDescription('Flush (empty) Magento cache directory');
    }

    public function getHelp()
    {
        return <<<HELP
The default cache backend is the files cache in Magento. The default
directory of that default cache backend is the directory "var/cache"
within the Magento web-root directory (should be blocked from external
access).

The cache:dir:flish Magerun command will remove all files within that
directory. This is currently the most purist form to reset default
caching configuration in Magento.

Flushing the cache directory can help to re-initialize the whole Magento
application after it got stuck in cached configuration like a half-done
cache initialization, old config data within the files cache and similar.
HELP;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            return;
        }

        $workingDirectory = getcwd();
        $magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $cacheDir = $magentoRootFolder . '/var/cache';

        $output->writeln(sprintf('<info>Flushing cache directory <comment>%s</comment></info>', $cacheDir));

        $this->verbose(sprintf('<debug>root-dir: <comment>%s</comment>', $magentoRootFolder));
        $this->verbose(sprintf('<debug>cwd: <comment>%s</comment>', $workingDirectory));

        $this->emptyDirectory($cacheDir);

        $output->writeln('Cache directory flushed');
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function emptyDirectory($path)
    {
        $errors = array();

        $dir = new FilesystemIterator($path);
        foreach ($dir as $file => $info) {
            if ($info->isDir()) {
                $this->verbose(
                    '<debug>Filesystem::recursiveRemoveDirectory() <comment>' . $file . '</comment></debug>'
                );
                if (!isset($fs)) {
                    $fs = new Filesystem();
                }
                if (!$fs->recursiveRemoveDirectory($file)) {
                    $errors[] = $file;
                };
            } else {
                $this->verbose('<debug>unlink() <comment>' . $file . '</comment></debug>');
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

    /**
     * @param string $message
     */
    private function verbose($message)
    {
        $output = $this->output;

        if (!$output) {
            return;
        }

        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln($message);
        }
    }
}
