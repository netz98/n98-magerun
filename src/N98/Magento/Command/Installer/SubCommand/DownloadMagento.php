<?php

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Console\Helper\ComposerHelper;
use N98\Util\Exec;
use N98\Util\ProcessArguments;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

/**
 * Class DownloadMagento
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class DownloadMagento extends AbstractSubCommand
{
    /**
     * @throws Exception
     * @return void
     */
    public function execute()
    {
        if ($this->input->getOption('noDownload')) {
            return;
        }

        try {
            $this->implementation();
        } catch (Exception $e) {
            throw new RuntimeException('Error while downloading magento, aborting install', 0, $e);
        }
    }

    private function implementation()
    {
        $package = $this->config['magentoVersionData'];
        $this->config->setArray('magentoPackage', $package);

        if (file_exists($this->config->getString('installationFolder') . '/app/etc/local.xml')) {

            /* @var QuestionHelper $dialog */
            $dialog = $this->command->getHelper('question');
            $skipInstallation = $dialog->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion('<question>A magento installation already exists in this folder. Skip download?</question> <comment>[y]</comment>: ', true)
            );

            if ($skipInstallation) {
                return;
            }

        }

        $this->composerCreateProject($package);
        $this->composerInstall();
    }


    /**
     * This method emulates the behavior of the `Magento\Framework\App\Filesystem\DirectoryList` component which, in
     * the end, reads the config directory path from the `$_SERVER['MAGE_DIR']['etc']['path']` if it exists and falls
     * back on the `app/etc` default value otherwise. Obviously is not possible to use the `DirectoryList` component
     * here because Magento has not been downloaded yet; so we have to emulate the original behavior.
     *
     * @return string
     */
    private function getConfigDir()
    {
        if (isset($_SERVER['MAGE_DIRS']['etc']['path'])) {
            return trim($_SERVER['MAGE_DIRS']['etc']['path'], DIRECTORY_SEPARATOR);
        }
        return 'app/etc';
    }

    /**
     * @param $package
     * @return void
     */
    private function composerCreateProject($package): void
    {
        $args = new ProcessArguments(array_merge($this->config['composer_bin'], ['create-project']));
        $args
            // Add composer options
            ->addArgs(isset($package['options']) ? $package['options'] : [])
            ->addArg('--no-dev')
            ->addArg('--no-install')
            // Add arguments
            ->addArg($package['package'])
            ->addArg($this->config->getString('installationFolder'))
            ->addArg($package['version']);

        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $args->addArg('-vvv');
        }

        $process = $args->createProcess();
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->writeln($process->getCommandLine());
        }

        $process->setTimeout(86400);
        $process->start();
        $code = $process->wait(function ($type, $buffer) {
            $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        if (Exec::CODE_CLEAN_EXIT !== $code) {
            throw new RuntimeException(
                'Non-zero exit code for composer create-project command: ' . $process->getCommandLine()
            );
        }
    }

    /**
     * @param string $pluginName
     * @return void
     */
    protected function composerAllowPlugins($pluginName): void
    {
        $process = new Process(
            array_merge(
                $this->config['composer_bin'],
                [
                    'config',
                    'allow-plugins.' . $pluginName,
                    'true'
                ]
            )
        );

        $process->setTimeout(86400);
        $process->start();
        $process->wait(function ($type, $buffer) {
            $this->output->write('composer > ' . $buffer, false);
        });
    }

    /**
     * @return void
     */
    protected function composerInstall(): void
    {
        $process = new Process(array_merge($this->config['composer_bin'], ['install']));
        $process->setTimeout(86400);
        $process->start();
        $process->wait(function ($type, $buffer) {
            $this->output->write('composer > ' . $buffer, false);
        });
    }
}
