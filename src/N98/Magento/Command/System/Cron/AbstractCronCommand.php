<?php

namespace N98\Magento\Command\System\Cron;

use AppendIterator;
use Mage;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractCronCommand extends AbstractMagentoCommand
{
    /**
     * @return array
     */
    protected function getJobs()
    {
        $table = array();

        // Get job configuration from XML and database. Expression priority is given to the database.

        /** @var $jobs AppendIterator */
        $jobs = array_reduce(
            array('crontab/jobs', 'default/crontab/jobs'), function(AppendIterator $carry, $path) {
                if ($jobConfig = Mage::getConfig()->getNode($path)) {
                    $carry->append(new \IteratorIterator($jobConfig->children()));
                };
                return $carry;
            }, new AppendIterator()
        );

        foreach ($jobs as $name => $job) {
            /* @var $job Mage_Core_Model_Config_Element */
            $schedule = $this->getSchedule($job);
            if (false !== $schedule) {
                $table[$name] = array('Job'  => $name) + (array) $schedule;
            } else {
                $table[$name] = array('Job'  => $name);
            }
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
     * parse a cron expression into an array, false-ly if unable to handle
     *
     * uses magento 1 internal parser of cron expressions
     *
     * @return array with five values (zero-indexed) or FALSE in case it does not exists.
     */
    private function parseCronExpression($expr) {

        if ($expr === 'always') {
            return array_fill(0, 5, '*');
        }

        /** @var $schedule Mage_Cron_Model_Schedule */
        $schedule = $this->_getModel('cron/schedule', 'Mage_Cron_Model_Schedule');

        try {
            $schedule->setCronExpr($expr);
        } catch (Mage_Cron_Exception $e) {
            return false;
        }

        $array = $schedule->getData('cron_expr_arr');

        $count = 0;
        foreach ($array as $expression) {
            if (++$count > 5) {
                // year is optional and never parsed
                break;
            }

            // parse each entry
            try {
                $schedule->matchCronExpression($expression, 1);
            } catch (Mage_Cron_Exception $e) {
                return false;
            }
        }

        return $array;
    }
}
