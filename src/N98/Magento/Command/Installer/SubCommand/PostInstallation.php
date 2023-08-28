<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Input\ArrayInput;

class PostInstallation extends AbstractSubCommand
{
    /**
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->getCommand()->getApplication()->setAutoExit(false);

        \chdir($this->config->getString('installationFolder'));
        $this->getCommand()->getApplication()->reinit();

        $this->output->writeln('<info>Reindex all after installation</info>');

        $indexerReindexInput = new ArrayInput(['command' => 'indexer:reindex']);
        $indexerReindexInput->setInteractive(false);
        $this->getCommand()->getApplication()->run(
            $indexerReindexInput,
            $this->output
        );

        /**
         * @TODO enable this after implementation of sys:check command
         */
        //$this->getCommand()->getApplication()->run(new StringInput('sys:check'), $this->output);
    }
}
