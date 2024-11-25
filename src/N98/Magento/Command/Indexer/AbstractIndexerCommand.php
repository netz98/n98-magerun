<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use DateInterval;
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
    protected function getIndexerModel()
    {
        /* @var Mage_Index_Model_Indexer $indexer */
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
    protected function _getIndexerModel()
    {
        trigger_error(__METHOD__ . ' moved, use ->getIndexerModel() instead', E_USER_DEPRECATED);
        return $this->getIndexerModel();
    }

    /**
     * @return array
     */
    protected function getIndexerList()
    {
        $list = [];
        $indexCollection = $this->getIndexerModel()->getProcessesCollection();
        foreach ($indexCollection as $indexer) {
            $lastReadbleRuntime = $this->getRuntime($indexer);
            $runtimeInSeconds = $this->getRuntimeInSeconds($indexer);
            $list[] = ['code'            => $indexer->getIndexerCode(), 'status'          => $indexer->getStatus(), 'last_runtime'    => $lastReadbleRuntime, 'runtime_seconds' => $runtimeInSeconds];
        }

        return $list;
    }

    /**
     * Returns a readable runtime
     *
     * @return string
     */
    protected function getRuntime(Mage_Index_Model_Process $mageIndexModelProcess)
    {
        $dateTime = new DateTimeUtils();
        $startTime = new \DateTime($mageIndexModelProcess->getStartedAt());
        $endTime = new \DateTime($mageIndexModelProcess->getEndedAt());
        if ($startTime > $endTime) {
            return 'index not finished';
        }
        return $dateTime->getDifferenceAsString($startTime, $endTime);
    }

    /**
     * Disable observer which try to create adminhtml session on CLI
     */
    protected function disableObservers()
    {
        $node = Mage::app()->getConfig()->getNode('adminhtml/events/core_locale_set_locale/observers/bind_locale');
        if ($node) {
            $node->appendChild(new Varien_Simplexml_Element('<type>disabled</type>'));
        }
    }

    /**
     * Returns the runtime in total seconds
     *
     * @return int
     */
    protected function getRuntimeInSeconds(Mage_Index_Model_Process $mageIndexModelProcess)
    {
        $startTimestamp = strtotime($mageIndexModelProcess->getStartedAt());
        $endTimestamp = strtotime($mageIndexModelProcess->getEndedAt());

        return $endTimestamp - $startTimestamp;
    }

    protected function writeEstimatedEnd(OutputInterface $output, Mage_Index_Model_Process $mageIndexModelProcess)
    {
        $runtimeInSeconds = $this->getRuntimeInSeconds($mageIndexModelProcess);

        /**
         * Try to estimate runtime. If index was aborted or never created we have a timestamp < 0
         */
        if ($runtimeInSeconds <= 0) {
            return;
        }

        $estimatedEnd = new \DateTime('now', new DateTimeZone('UTC'));
        $estimatedEnd->add(new DateInterval('PT' . $runtimeInSeconds . 'S'));

        $output->writeln(
            sprintf('<info>Estimated end: <comment>%s</comment></info>', $estimatedEnd->format('Y-m-d H:i:s T'))
        );
    }

    protected function writeSuccessResult(
        OutputInterface $output,
        Mage_Index_Model_Process $mageIndexModelProcess,
        \DateTime $startTime,
        \DateTime $endTime
    ) {
        $output->writeln(
            sprintf(
                '<info>Successfully reindexed <comment>%s</comment> (Runtime: <comment>%s</comment>)</info>',
                $mageIndexModelProcess->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime)
            )
        );
    }

    /**
     * @param string $errorMessage
     */
    protected function writeFailedResult(
        OutputInterface $output,
        Mage_Index_Model_Process $mageIndexModelProcess,
        \DateTime $startTime,
        \DateTime $endTime,
        $errorMessage
    ) {
        $output->writeln(
            sprintf(
                '<error>Reindex finished with error message "%s". %s</error> (Runtime: <comment>%s</comment>)</error>',
                $errorMessage,
                $mageIndexModelProcess->getIndexerCode(),
                DateTimeUtils::difference($startTime, $endTime)
            )
        );
    }

    /**
     * @return bool
     */
    protected function executeProcesses(OutputInterface $output, array $processes)
    {
        $isSuccessful = true;

        try {
            \Mage::dispatchEvent('shell_reindex_init_process');
            foreach ($processes as $process) {
                if (!$this->executeProcess($output, $process)) {
                    $isSuccessful = false;
                }
            }

            \Mage::dispatchEvent('shell_reindex_finalize_process');
        } catch (Exception $exception) {
            $isSuccessful = false;
            \Mage::dispatchEvent('shell_reindex_finalize_process');
        }

        return $isSuccessful;
    }

    /**
     * @return bool
     */
    private function executeProcess(OutputInterface $output, Mage_Index_Model_Process $mageIndexModelProcess)
    {
        $output->writeln(
            sprintf('<info>Started reindex of: <comment>%s</comment></info>', $mageIndexModelProcess->getIndexerCode())
        );
        $this->writeEstimatedEnd($output, $mageIndexModelProcess);

        $startTime = \Carbon\Carbon::now();

        $isSuccessful = true;
        $errorMessage = '';

        try {
            $mageIndexModelProcess->reindexEverything();
            \Mage::dispatchEvent($mageIndexModelProcess->getIndexerCode() . '_shell_reindex_after');
        } catch (Exception $exception) {
            $errorMessage = $exception->getMessage();
            $isSuccessful = false;
        }

        $endTime = \Carbon\Carbon::now();

        if ($isSuccessful) {
            $this->writeSuccessResult($output, $mageIndexModelProcess, $startTime, $endTime);
        } else {
            $this->writeFailedResult($output, $mageIndexModelProcess, $startTime, $endTime, $errorMessage);
        }

        return $isSuccessful;
    }
}
