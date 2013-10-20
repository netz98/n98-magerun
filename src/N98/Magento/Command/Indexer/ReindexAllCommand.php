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

        $this->setHelp('Loops all magento indexes and triggers reindex.');
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

            $this->disableObservers();

            try {
                \Mage::dispatchEvent('shell_reindex_init_process');
                $indexCollection = $this->_getIndexerModel()->getProcessesCollection();
                foreach ($indexCollection as $indexer) {
                    $indexer->reindexEverything();
                    \Mage::dispatchEvent($indexer->getIndexerCode() . '_shell_reindex_after');
                    $output->writeln(
                        '<info>Successfully reindexed</info> <comment>' . $indexer->getIndexerCode() . '</comment>'
                    );
                }
                \Mage::dispatchEvent('shell_reindex_init_process');
            } catch (\Exception $e) {
                \Mage::dispatchEvent('shell_reindex_init_process');
            }
        }
    }
}