<?php

namespace N98\Magento\Command\Indexer;

use Mage_Index_Model_Process;
use Mage_Index_Model_Resource_Process_Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexAllCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:reindex:all')
            ->setDescription('Reindex all magento indexes')
        ;

        $this->setHelp('Loops all magento indexes and triggers reindex.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $this->disableObservers();

        /* @var Mage_Index_Model_Resource_Process_Collection|Mage_Index_Model_Process[] $processes */
        $processes = $this->getIndexerModel()->getProcessesCollection();

        if (!$this->executeProcesses($output, iterator_to_array($processes, false))) {
            return 1;
        }

        return 0;
    }
}
