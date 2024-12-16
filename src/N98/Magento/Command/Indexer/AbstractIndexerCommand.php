<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Mage;
use Mage_Index_Model_Indexer;
use Mage_Index_Model_Process;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\DateTime as DateTimeUtils;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;
use Varien_Simplexml_Element;

/**
 * Class AbstractIndexerCommand
 *
 * @package N98\Magento\Command\Indexer
 */
class AbstractIndexerCommand extends AbstractMagentoCommand
{
    protected function getIndexerModel(): Mage_Index_Model_Indexer
    {
        $indexer = Mage::getModel('index/indexer');
        if (!$indexer instanceof Mage_Index_Model_Indexer) {
            throw new UnexpectedValueException('Failure getting indexer model');
        }

        return $indexer;
    }

    protected function getIndexerList(): array
    {
        $list = [];
        $indexCollection = $this->getIndexerModel()->getProcessesCollection();
        foreach ($indexCollection as $indexer) {
            $lastReadableRuntime = $this->getRuntime($indexer);
            $runtimeInSeconds = $this->getRuntimeInSeconds($indexer);
            $list[] = [
                'code'            => $indexer->getIndexerCode(),
                'status'          => $indexer->getStatus(),
                'last_runtime'    => $lastReadableRuntime,
                'runtime_seconds' => $runtimeInSeconds,
            ];
        }

        return $list;
    }

    /**
     * Returns a readable runtime
     */
    protected function getRuntime(Mage_Index_Model_Process $mageIndexModelProcess): string
    {
        $dateTime   = new DateTimeUtils();
        $startTime  = new DateTime((string) $mageIndexModelProcess->getStartedAt());
        $endTime    = new DateTime((string) $mageIndexModelProcess->getEndedAt());
        if ($startTime > $endTime) {
            return 'index not finished';
        }

        return $dateTime->getDifferenceAsString($startTime, $endTime);
    }

    /**
     * Disable observer which try to create adminhtml session on CLI
     */
    protected function disableObservers(): void
    {
        $node = Mage::app()->getConfig()->getNode('adminhtml/events/core_locale_set_locale/observers/bind_locale');
        if ($node) {
            $node->appendChild(new Varien_Simplexml_Element('<type>disabled</type>'));
        }
    }

    /**
     * Returns the runtime in total seconds
     */
    protected function getRuntimeInSeconds(Mage_Index_Model_Process $mageIndexModelProcess): int
    {
        $startTimestamp = strtotime((string) $mageIndexModelProcess->getStartedAt());
        $endTimestamp   = strtotime((string) $mageIndexModelProcess->getEndedAt());
        return $endTimestamp - $startTimestamp;
    }

    protected function writeEstimatedEnd(OutputInterface $output, Mage_Index_Model_Process $mageIndexModelProcess): void
    {
        $runtimeInSeconds = $this->getRuntimeInSeconds($mageIndexModelProcess);

        /**
         * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
         */
        if ($runtimeInSeconds <= 0) {
            return;
        }

        $estimatedEnd = new DateTime('now', new DateTimeZone('UTC'));
        $estimatedEnd->add(new DateInterval('PT' . $runtimeInSeconds . 'S'));

        $output->writeln(
            sprintf('<info>Estimated end: <comment>%s</comment></info>', $estimatedEnd->format('Y-m-d H:i:s T')),
        );
    }

    protected function writeSuccessResult(
        OutputInterface $output,
        Mage_Index_Model_Process $mageIndexModelProcess,
        DateTime $startTime,
        DateTime $endTime
    ): void {
        $output->writeln(
            sprintf(
                '<info>Successfully re-indexed <comment>%s</comment> (Runtime: <comment>%s</comment>)</info>',
                $mageIndexModelProcess->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime),
            ),
        );
    }

    protected function writeFailedResult(
        OutputInterface $output,
        Mage_Index_Model_Process $mageIndexModelProcess,
        DateTime $startTime,
        DateTime $endTime,
        string $errorMessage
    ): void {
        $output->writeln(
            sprintf(
                '<error>Reindex finished with error message "%s". %s</error> (Runtime: <comment>%s</comment>)</error>',
                $errorMessage,
                $mageIndexModelProcess->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime),
            ),
        );
    }

    protected function executeProcesses(OutputInterface $output, array $processes): bool
    {
        $isSuccessful = true;

        try {
            Mage::dispatchEvent('shell_reindex_init_process');
            foreach ($processes as $process) {
                if (!$this->executeProcess($output, $process)) {
                    $isSuccessful = false;
                }
            }

            Mage::dispatchEvent('shell_reindex_finalize_process');
        } catch (Exception $exception) {
            $isSuccessful = false;
            Mage::dispatchEvent('shell_reindex_finalize_process');
        }

        return $isSuccessful;
    }

    private function executeProcess(OutputInterface $output, Mage_Index_Model_Process $mageIndexModelProcess): bool
    {
        $output->writeln(
            sprintf('<info>Started reindex of: <comment>%s</comment></info>', $mageIndexModelProcess->getIndexerCode()),
        );
        $this->writeEstimatedEnd($output, $mageIndexModelProcess);

        $startTime = Carbon::now();

        $isSuccessful = true;
        $errorMessage = '';

        try {
            $mageIndexModelProcess->reindexEverything();
            Mage::dispatchEvent($mageIndexModelProcess->getIndexerCode() . '_shell_reindex_after');
        } catch (Exception $exception) {
            $errorMessage = $exception->getMessage();
            $isSuccessful = false;
        }

        $endTime = Carbon::now();

        if ($isSuccessful) {
            $this->writeSuccessResult($output, $mageIndexModelProcess, $startTime, $endTime);
        } else {
            $this->writeFailedResult($output, $mageIndexModelProcess, $startTime, $endTime, $errorMessage);
        }

        return $isSuccessful;
    }
}
