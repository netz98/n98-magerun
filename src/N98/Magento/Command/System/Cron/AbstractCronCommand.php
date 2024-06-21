<?php

namespace N98\Magento\Command\System\Cron;

use AppendIterator;
use Iterator;
use IteratorIterator;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractCommand;

abstract class AbstractCronCommand extends AbstractCommand
{
    /**
     * @return array<string, array<string, Mage_Core_Model_Config_Element|string|null>>
     * @throws Mage_Core_Exception
     */
    protected function getJobs(): array
    {
        $table = [];

        $jobs = $this->getJobConfigElements();

        /**
         * @var string $name
         * @var Mage_Core_Model_Config_Element $job
         */
        foreach ($jobs as $name => $job) {
            $model = null;
            if (isset($job->run->model)) {
                $model = $job->run->model;
            }
            $table[$name] = ['Job' => $name, 'Model' => $model] + $this->getSchedule($job);
        }

        ksort($table, SORT_STRING);

        return $table;
    }

    /**
     * @param Mage_Core_Model_Config_Element $job
     * @return array<string, string>|false of five cron values,keyed by 'm', 'h', 'D', 'M' and 'WD'
     * @throws Mage_Core_Exception
     */
    protected function getSchedule(Mage_Core_Model_Config_Element $job)
    {
        $keys = ['m', 'h', 'D', 'M', 'WD'];
        $expr = null;

        if (isset($job->schedule->config_path)) {
            $expr = Mage::getStoreConfig((string) $job->schedule->config_path);
        } elseif (isset($job->schedule->cron_expr)) {
            $expr = $job->schedule->cron_expr;
        }

        if (is_string($expr) && $cronExpressions = $this->parseCronExpression($expr)) {
            return array_combine($keys, $cronExpressions);
        }

        return array_combine($keys, array_fill(0, 5, '  '));
    }

    /**
     * Get job configuration from XML and database. Expression priority is given to the database.
     *
     * @return AppendIterator<mixed, mixed, Iterator<mixed, mixed>>
     */
    private function getJobConfigElements(): AppendIterator
    {
        $jobs = new AppendIterator();

        $paths = ['crontab/jobs', 'default/crontab/jobs'];

        foreach ($paths as $path) {
            if ($jobConfig = $this->_getMageConfig()->getNode($path)) {
                $jobs->append(new IteratorIterator($jobConfig->children()));
            }
        }

        return $jobs;
    }

    /**
     * parse a cron expression into an array, false-ly if unable to handle
     *
     * uses magento 1 internal parser of cron expressions
     *
     * @param string $expr
     * @return array<int, string>|null with five values (zero-indexed) or FALSE in case it does not exist.
     * @throws Mage_Core_Exception
     */
    private function parseCronExpression(string $expr): ?array
    {
        if ((string)$expr === 'always') {
            return array_fill(0, 5, '*');
        }

        /** @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');

        try {
            $schedule->setCronExpr($expr);
        } catch (Mage_Cron_Exception $e) {
            return null;
        }

        /** @var array<int, string> $array */
        $array = $schedule->getData('cron_expr_arr');

        $array = array_slice($array, 0, 5); // year is optional and never parsed

        // parse each entry
        foreach ($array as $expression) {
            try {
                $schedule->matchCronExpression($expression, 1);
            } catch (Mage_Cron_Exception $e) {
                return null;
            }
        }

        return $array;
    }
}
