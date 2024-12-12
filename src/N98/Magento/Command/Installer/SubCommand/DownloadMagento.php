<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Exec;
use N98\Util\ProcessArguments;
use Symfony\Component\Console\Exception\RuntimeException;
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
     */
    public function execute(): void
    {
        if ($this->input->getOption('noDownload')) {
            return;
        }

        try {
            $this->implementation();
        } catch (Exception $exception) {
            throw new RuntimeException('Error while downloading magento, aborting install', 0, $exception);
        }
    }

    private function implementation(): void
    {
        $package = $this->config['magentoVersionData'];
        $this->config->setArray('magentoPackage', $package);

        if (file_exists($this->config->getString('installationFolder') . '/app/etc/local.xml')) {
            $dialog = $this->command->getQuestionHelper();
            $skipInstallation = $dialog->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion('<question>A magento installation already exists in this folder. Skip download?</question> <comment>[y]</comment>: ', true),
            );

            if ($skipInstallation) {
                return;
            }

        }

        $this->composerCreateProject($package);
        $this->composerInstall();
    }

    private function composerCreateProject(array $package): void
    {
        $processArguments = new ProcessArguments(array_merge($this->config['composer_bin'], ['create-project']));
        $processArguments
            // Add composer options
            ->addArgs($package['options'] ?? [])
            ->addArg('--no-dev')
            ->addArg('--no-install')
            // Add arguments
            ->addArg($package['package'])
            ->addArg($this->config->getString('installationFolder'))
            ->addArg($package['version']);

        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $processArguments->addArg('-vvv');
        }

        $process = $processArguments->createProcess();
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->writeln($process->getCommandLine());
        }

        $process->setTimeout(86400);
        $process->start();

        $code = $process->wait(function ($type, $buffer): void {
            $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        if (Exec::CODE_CLEAN_EXIT !== $code) {
            throw new RuntimeException(
                'Non-zero exit code for composer create-project command: ' . $process->getCommandLine(),
            );
        }
    }

    protected function composerAllowPlugins(string $pluginName): void
    {
        $process = new Process(
            array_merge(
                $this->config['composer_bin'],
                [
                    'config',
                    'allow-plugins.' . $pluginName,
                    'true',
                ],
            ),
        );

        $process->setTimeout(86400);
        $process->start();
        $process->wait(function ($type, $buffer): void {
            $this->output->write('composer > ' . $buffer, false);
        });
    }

    protected function composerInstall(): void
    {
        $process = new Process(array_merge($this->config['composer_bin'], ['install']));
        $process->setTimeout(86400);
        $process->start();
        $process->wait(function ($type, $buffer): void {
            $this->output->write('composer > ' . $buffer, false);
        });
    }
}
