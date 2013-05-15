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
        if ($this->initMagento($output)) {
            $this->writeSection($output, 'Reindex');
            $this->disableObservers();
            $indexCode = $input->getArgument('index_code');
            $indexerList = $this->getIndexerList();
            if ($indexCode === null) {
                $question = array();
                foreach ($indexerList as $key => $indexer) {
                    $question[] = '<comment>' . str_pad('[' . ($key+1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' . str_pad($indexer['code'], 40, ' ', STR_PAD_RIGHT) . ' <info>(last runtime: ' . $indexer['last_runtime'] . ')</info>' . "\n";
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
            $output->writeln('<info>Started reindex of: <comment>' . $indexCode . '</comment></info>');

            /**
             * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
             */
            $runtimeInSeconds = $this->getRuntimeInSeconds($process);
            if ($runtimeInSeconds > 0) {
                $estimatedEnd = new \DateTime('now', new \DateTimeZone('UTC'));
                $estimatedEnd->add(new \DateInterval('PT' . $runtimeInSeconds . 'S'));
                $output->writeln('<info>Estimated end: <comment>' . $estimatedEnd->format('Y-m-d H:i:s T') . '</comment></info>');
            }

            $startTime = new \DateTime('now');
            $dateTimeUtils = new \N98\Util\DateTime();
            $process->reindexEverything();
            $endTime = new \DateTime('now');
            $output->writeln('<info>Successfully reindexed <comment>' . $indexCode . '</comment> (Runtime: <comment>' . $dateTimeUtils->getDifferenceAsString($startTime, $endTime) .'</comment>)</info>');
        }
    }
}