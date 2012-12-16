<?php

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexAllCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:reindex:all')
            ->setDescription('Reindex all magento indexes')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $indexCollection = $this->_getIndexerModel()->getProcessesCollection();
            foreach ($indexCollection as $indexer) {
                $indexer->reindexEverything();
                $output->writeln('<info>Successfully reindexed</info> <comment>' . $indexer->getIndexerCode() . '</comment>');
            }
        }
    }
}