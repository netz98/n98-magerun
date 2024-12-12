<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Input\ArrayInput;

use function chdir;

/**
 * Class PostInstallation
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class PostInstallation extends AbstractSubCommand
{
    /**
     * @throws Exception
     */
    public function execute(): void
    {
        $this->getCommand()->getApplication()->setAutoExit(false);

        chdir($this->config->getString('installationFolder'));
        $this->getCommand()->getApplication()->reinit();

        $this->output->writeln('<info>Reindex all after installation</info>');

        $arrayInput = new ArrayInput(['command' => 'index:reindex:all']);
        $arrayInput->setInteractive(false);
        $this->getCommand()->getApplication()->run(
            $arrayInput,
            $this->output,
        );

        /**
         * @TODO enable this after implementation of sys:check command
         */
        //$this->getCommand()->getApplication()->run(new StringInput('sys:check'), $this->output);
    }
}
