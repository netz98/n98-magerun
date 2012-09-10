<?php

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:reindex')
            ->addArgument('index_code', InputArgument::OPTIONAL, 'Code of indexer.')
            ->setDescription('Reindex a magento index by code')
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
            $this->writeSection($output, 'Reindex');
            $indexCode = $input->getArgument('index_code');
            if ($indexCode === null) {
                $question = array();
                $indexerList = $this->getIndexerList();

                foreach ($indexerList as $key => $indexer) {
                    $question[] = '<comment>[' . ($key+1) . ']</comment> ' . $indexer['code'] . "\n";
                }
                $question[] = '<question>Please select a indexer:</question>';

                $indexCode = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($indexerList) {
                    if (!isset($indexerList[$typeInput - 1])) {
                        throw new \InvalidArgumentException('Invalid indexer');
                    }
                    return $indexerList[$typeInput - 1]['code'];
                });
            }
            $process = $this->_getIndexerModel()->getProcessByCode($indexCode);
            if (!$process) {
                throw new \InvalidArgumentException('Indexer was not found!');
            }
            $process->reindexEverything();
            $output->writeln('<info>Successfully reindexed ' . $indexCode . '</info>');
        }
    }
}