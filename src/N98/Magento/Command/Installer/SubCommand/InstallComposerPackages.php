<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Process\Process;

/**
 * Class InstallComposerPackages
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class InstallComposerPackages extends AbstractSubCommand
{
    /**
     * Check PHP environment against minimal required settings modules
     *
     * @throws Exception
     */
    public function execute(): void
    {
        $this->output->writeln('<comment>Install composer packages</comment>');
        $process = new Process(array_merge($this->config['composer_bin'], ['install']));
        $process->setTimeout(86400);

        $process->start();
        $process->wait(function ($type, $buffer): void {
            $this->output->write('composer > ' . $buffer, false);
        });
    }
}
