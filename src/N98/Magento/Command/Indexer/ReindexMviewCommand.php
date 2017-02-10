<?php

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexMviewCommand extends AbstractMviewIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:reindex:mview')
            ->addArgument('table_name', InputArgument::REQUIRED, 'View table name"')
            ->setDescription('Reindex a magento index by code using the materialised view functionality');

        $help = <<<HELP
Trigger a mview index by table_name.

   $ n98-magerun.phar index:reindex:mview [table_name]
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }
        $tableName = $input->getArgument('table_name');

        $indexers = $this->getIndexers();

        if (!array_key_exists($tableName, $indexers)) {
            throw new \InvalidArgumentException("$tableName is not a view table");
        }

        $indexerData = $indexers[$tableName];
        $indexTable = (string) $indexerData->index_table;
        $actionName = (string) $indexerData->action_model->changelog;

        $client = $this->getMviewClient();
        $client->init($indexTable);
        if (!$client->getMetadata()->getId()) {
            throw new \InvalidArgumentException("Could not load metadata for $tableName");
        }

        $output->writeln("<info>Starting mview indexer <comment>{$indexTable}</comment> with action <comment>{$actionName}</comment> </info>");
        $client->execute($actionName);
        $output->writeln("<info>Done</info>");
    }
}
