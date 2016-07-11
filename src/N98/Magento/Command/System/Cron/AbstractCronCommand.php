<?php

namespace N98\Magento\Command\System\Cron;

use AppendIterator;
use Mage;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use Traversable;

abstract class AbstractCronCommand extends AbstractMagentoCommand
{
    /**
     * @return array
     */
    protected function getJobs()
    {
        $table = array();

        $jobs = $this->getJobConfigElements();

        foreach ($jobs as $name => $job) {
            $model = null;
            if (isset($job->run->model)) {
                $model = $job->run->model;
            }
            $table[$name] = array('Job' => $name, 'Model' => $model) + $this->getSchedule($job);
        }

        ksort($table, SORT_STRING);

        return $table;
    }

    /**
     * @param  Mage_Core_Model_Config_Element $job
     * @return array of five cron values,keyed by 'm', 'h', 'D', 'M' and 'WD'
     */
    protected function getSchedule(Mage_Core_Model_Config_Element $job)
    {
        $keys = array('m', 'h', 'D', 'M', 'WD');
        $expr = null;

        if (isset($job->schedule->config_path)) {
            $expr = Mage::getStoreConfig((string) $job->schedule->config_path);
        } elseif (isset($job->schedule->cron_expr)) {
            $expr = $job->schedule->cron_expr;
        }

        if ($cronExpressions = $this->parseCronExpression($expr)) {
            return array_combine($keys, $cronExpressions);
        }

        return array_combine($keys, array_fill(0, 5, '  '));
    }

    /**
     * Get job configuration from XML and database. Expression priority is given to the database.
     *
     * @return Traversable|Mage_Core_Model_Config_Element[]
     */
    private function getJobConfigElements()
    {
        $jobs = new AppendIterator();

        $paths = array('crontab/jobs', 'default/crontab/jobs');

        foreach ($paths as $path) {
            if ($jobConfig = Mage::getConfig()->getNode($path)) {
                $jobs->append(new \IteratorIterator($jobConfig->children()));
            };
        }

        return $jobs;
    }

    /**
     * parse a cron expression into an array, false-ly if unable to handle
     *
     * uses magento 1 internal parser of cron expressions
     *
     * @return array with five values (zero-indexed) or FALSE in case it does not exists.
     */
    private function parseCronExpression($expr)
    {
        if ($expr === 'always') {
            return array_fill(0, 5, '*');
        }

        /** @var $schedule Mage_Cron_Model_Schedule */
        $schedule = Mage::getModel('cron/schedule');

        try {
            $schedule->setCronExpr($expr);
        } catch (Mage_Cron_Exception $e) {
            return false;
        }

        $array = $schedule->getData('cron_expr_arr');

        $array = array_slice($array, 0, 5); // year is optional and never parsed

        // parse each entry
        foreach ($array as $expression) {
            try {
                $schedule->matchCronExpression($expression, 1);
            } catch (Mage_Cron_Exception $e) {
                return false;
            }
        }

        return $array;
    }
}
