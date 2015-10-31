<?php

namespace N98\Magento\Command\Indexer;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

        $help = <<<HELP
Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

   $ n98-magerun.phar index:reindex [code]


Since 1.75.0 it's possible to run mutiple indexers by seperating code with a comma.

i.e.

   $ n98-magerun.phar index:reindex catalog_product_attribute,tag_summary

If no index is provided as argument you can select indexers from menu by "number" like "1,3" for first and third
indexer.
HELP;
        $this->setHelp($help);
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
            $this->writeSection($output, 'Reindex');
            $this->disableObservers();
            $indexCode = $input->getArgument('index_code');
            $indexerList = $this->getIndexerList();
            if ($indexCode === null) {
                $question = array();
                foreach ($indexerList as $key => $indexer) {
                    $question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ', STR_PAD_RIGHT) . '</comment> ' . str_pad($indexer['code'], 40, ' ', STR_PAD_RIGHT) . ' <info>(last runtime: ' . $indexer['last_runtime'] . ')</info>' . "\n";
                }
                $question[] = '<question>Please select a indexer:</question>';

                $indexCodes = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($indexerList) {
                    if (strstr($typeInput, ',')) {
                        $typeInputs = \N98\Util\BinaryString::trimExplodeEmpty(',', $typeInput);
                    } else {
                        $typeInputs = array($typeInput);
                    }

                    $returnCodes = array();
                    foreach ($typeInputs as $typeInput) {
                        if (!isset($indexerList[$typeInput - 1])) {
                            throw new InvalidArgumentException('Invalid indexer');
                        }

                        $returnCodes[] = $indexerList[$typeInput - 1]['code'];
                    }

                    return $returnCodes;
                });
            } else {
                // take cli argument
                $indexCodes = \N98\Util\BinaryString::trimExplodeEmpty(',', $indexCode);
            }

            foreach ($indexCodes as $indexCode) {

                try {
                    \Mage::dispatchEvent('shell_reindex_init_process');
                    $process = $this->_getIndexerModel()->getProcessByCode($indexCode);
                    if (!$process) {
                        throw new InvalidArgumentException('Indexer was not found!');
                    }
                    $output->writeln('<info>Started reindex of: <comment>' . $indexCode . '</comment></info>');

                    /**
                     * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
                     */
                    $runtimeInSeconds = $this->getRuntimeInSeconds($process);
                    if ($runtimeInSeconds > 0) {
                        $estimatedEnd = new \DateTime('now', new \DateTimeZone('UTC'));
                        $estimatedEnd->add(new \DateInterval('PT' . $runtimeInSeconds . 'S'));
                        $output->writeln(
                            '<info>Estimated end: <comment>' . $estimatedEnd->format('Y-m-d H:i:s T') . '</comment></info>'
                        );
                    }

                    $startTime = new \DateTime('now');
                    $dateTimeUtils = new \N98\Util\DateTime();
                    $process->reindexEverything();
                    \Mage::dispatchEvent($process->getIndexerCode() . '_shell_reindex_after');
                    $endTime = new \DateTime('now');
                    $output->writeln(
                        '<info>Successfully reindexed <comment>' . $indexCode . '</comment> (Runtime: <comment>' . $dateTimeUtils->getDifferenceAsString(
                            $startTime,
                            $endTime
                        ) . '</comment>)</info>'
                    );
                    \Mage::dispatchEvent('shell_reindex_finalize_process');
                } catch (Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    \Mage::dispatchEvent('shell_reindex_finalize_process');
                }
            }
        }
    }
}
