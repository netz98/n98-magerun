<?php

namespace N98\Magento\Command\Indexer;

use Exception;
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
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
                    $output->writeln('<info>Started reindex of: <comment>' . $indexer->getIndexerCode() . '</comment></info>');
                    /**
                     * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
                     */
                    $runtimeInSeconds = $this->getRuntimeInSeconds($indexer);
                    if ($runtimeInSeconds > 0) {
                        $estimatedEnd = new \DateTime('now', new \DateTimeZone('UTC'));
                        $estimatedEnd->add(new \DateInterval('PT' . $runtimeInSeconds . 'S'));
                        $output->writeln(
                            '<info>Estimated end: <comment>' . $estimatedEnd->format('Y-m-d H:i:s T') . '</comment></info>'
                        );
                    }
                    $startTime = new \DateTime('now');
                    $dateTimeUtils = new \N98\Util\DateTime();
                    $indexer->reindexEverything();
                    \Mage::dispatchEvent($indexer->getIndexerCode() . '_shell_reindex_after');
                    $endTime = new \DateTime('now');
                    $output->writeln(
                        '<info>Successfully reindexed <comment>' . $indexer->getIndexerCode() . '</comment> (Runtime: <comment>' . $dateTimeUtils->getDifferenceAsString(
                            $startTime,
                            $endTime
                        ) . '</comment>)</info>'
                    );
                }
                \Mage::dispatchEvent('shell_reindex_init_process');
            } catch (Exception $e) {
                \Mage::dispatchEvent('shell_reindex_init_process');
            }
        }
    }
}
