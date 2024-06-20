<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

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
    /**
     * @return Mage_Index_Model_Indexer
     */
    protected function getIndexerModel(): Mage_Index_Model_Indexer
    {
        $indexer = Mage::getModel('index/indexer');
        if (!$indexer instanceof Mage_Index_Model_Indexer) {
            throw new UnexpectedValueException('Failure getting indexer model');
        }

        return $indexer;
    }

    /**
     * @return Mage_Index_Model_Indexer
     * @deprecated since 1.97.28
     */
    protected function _getIndexerModel(): Mage_Index_Model_Indexer
    {
        trigger_error(__METHOD__ . ' moved, use ->getIndexerModel() instead', E_USER_DEPRECATED);
        return $this->getIndexerModel();
    }

    /**
     * @return array<int, array<string, int|string>>
     * @throws Exception
     */
    protected function getIndexerList(): array
    {
        $list = [];
        $indexCollection = $this->getIndexerModel()->getProcessesCollection();
        /** @var Mage_Index_Model_Process $indexer */
        foreach ($indexCollection as $indexer) {
            $lastReadableRuntime = $this->getRuntime($indexer);
            $runtimeInSeconds = $this->getRuntimeInSeconds($indexer);
            $list[] = [
                'code'            => $indexer->getIndexerCode(),
                'status'          => $indexer->getStatus(),
                'last_runtime'    => $lastReadableRuntime,
                'runtime_seconds' => $runtimeInSeconds
            ];
        }

        return $list;
    }

    /**
     * Returns a readable runtime
     *
     * @param Mage_Index_Model_Process $indexer
     * @return string
     * @throws Exception
     */
    protected function getRuntime(Mage_Index_Model_Process $indexer): string
    {
        $dateTimeUtils = new DateTimeUtils();
        $startTime = new DateTime($indexer->getStartedAt());
        $endTime = new DateTime($indexer->getEndedAt());
        if ($startTime > $endTime) {
            return 'index not finished';
        }
        return $dateTimeUtils->getDifferenceAsString($startTime, $endTime);
    }

    /**
     * Disable observer which try to create adminhtml session on CLI
     */
    protected function disableObservers(): void
    {
        $node = $this->_getMageConfig()->getNode('adminhtml/events/core_locale_set_locale/observers/bind_locale');
        if ($node) {
            $node->appendChild(new Varien_Simplexml_Element('<type>disabled</type>'));
        }
    }

    /**
     * Returns the runtime in total seconds
     *
     * @param Mage_Index_Model_Process $indexer
     * @return int
     */
    protected function getRuntimeInSeconds(Mage_Index_Model_Process $indexer): int
    {
        $startTimestamp = strtotime($indexer->getStartedAt());
        $endTimestamp = strtotime($indexer->getEndedAt());

        return $endTimestamp - $startTimestamp;
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Index_Model_Process $process
     * @throws Exception
     */
    protected function writeEstimatedEnd(OutputInterface $output, Mage_Index_Model_Process $process): void
    {
        $runtimeInSeconds = $this->getRuntimeInSeconds($process);

        /**
         * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
         */
        if ($runtimeInSeconds <= 0) {
            return;
        }

        $estimatedEnd = new DateTime('now', new DateTimeZone('UTC'));
        $estimatedEnd->add(new DateInterval('PT' . $runtimeInSeconds . 'S'));
        $output->writeln(
            sprintf('<info>Estimated end: <comment>%s</comment></info>', $estimatedEnd->format('Y-m-d H:i:s T'))
        );
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Index_Model_Process $process
     * @param DateTime $startTime
     * @param DateTime $endTime
     */
    protected function writeSuccessResult(
        OutputInterface $output,
        Mage_Index_Model_Process $process,
        DateTime $startTime,
        DateTime $endTime
    ): void {
        $output->writeln(
            sprintf(
                '<info>Successfully reindexed <comment>%s</comment> (Runtime: <comment>%s</comment>)</info>',
                $process->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime)
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Index_Model_Process $process
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @param string $errorMessage
     */
    protected function writeFailedResult(
        OutputInterface $output,
        Mage_Index_Model_Process $process,
        DateTime $startTime,
        DateTime $endTime,
        string $errorMessage
    ): void {
        $output->writeln(
            sprintf(
                '<error>Reindex finished with error message "%s". %s</error> (Runtime: <comment>%s</comment>)</error>',
                $errorMessage,
                $process->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime)
            )
        );
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Index_Model_Process[] $processes
     * @return bool
     */
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
        } catch (Exception $e) {
            $isSuccessful = false;
            Mage::dispatchEvent('shell_reindex_finalize_process');
        }

        return $isSuccessful;
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Index_Model_Process $process
     * @return bool
     * @throws Exception
     */
    private function executeProcess(OutputInterface $output, Mage_Index_Model_Process $process): bool
    {
        $output->writeln(
            sprintf('<info>Started reindex of: <comment>%s</comment></info>', $process->getIndexerCode())
        );
        $this->writeEstimatedEnd($output, $process);

        $startTime = new DateTime('now');

        $isSuccessful = true;
        $errorMessage = '';

        try {
            $process->reindexEverything();
            Mage::dispatchEvent($process->getIndexerCode() . '_shell_reindex_after');
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $isSuccessful = false;
        }

        $endTime = new DateTime('now');

        if ($isSuccessful) {
            $this->writeSuccessResult($output, $process, $startTime, $endTime);
        } else {
            $this->writeFailedResult($output, $process, $startTime, $endTime, $errorMessage);
        }

        return $isSuccessful;
    }
}
