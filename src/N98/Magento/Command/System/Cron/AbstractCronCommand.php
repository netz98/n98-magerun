<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use AppendIterator;
use IteratorIterator;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use Traversable;

/**
 * Class AbstractCronCommand
 *
 * @package N98\Magento\Command\System\Cron
 */
abstract class AbstractCronCommand extends AbstractMagentoCommand
{
    /**
     * @return array
     */
    protected function getJobs()
    {
        $table = [];

        $jobConfigElements = $this->getJobConfigElements();

        foreach ($jobConfigElements as $name => $job) {
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
     * @return array|false of five cron values,keyed by 'm', 'h', 'D', 'M' and 'WD'
     * @throws Mage_Core_Exception
     */
    protected function getSchedule(Mage_Core_Model_Config_Element $mageCoreModelConfigElement)
    {
        $keys = ['m', 'h', 'D', 'M', 'WD'];
        $expr = null;

        if (isset($mageCoreModelConfigElement->schedule->config_path)) {
            $expr = Mage::getStoreConfig((string) $mageCoreModelConfigElement->schedule->config_path);
        } elseif (isset($mageCoreModelConfigElement->schedule->cron_expr)) {
            $expr = $mageCoreModelConfigElement->schedule->cron_expr;
        }

        if ($cronExpressions = $this->parseCronExpression($expr)) {
            return array_combine($keys, $cronExpressions);
        }

        return array_combine($keys, array_fill(0, 5, '  '));
    }

    /**
     * Get job configuration from XML and database. Expression priority is given to the database.
     */
    private function getJobConfigElements(): AppendIterator
    {
        $jobs = new AppendIterator();

        $paths = ['crontab/jobs', 'default/crontab/jobs'];
        foreach ($paths as $path) {
            if ($jobConfig = Mage::getConfig()->getNode($path)) {
                $jobs->append(new IteratorIterator($jobConfig->children()));
            };
        }

        return $jobs;
    }

    /**
     * parse a cron expression into an array, false-ly if unable to handle
     *
     * uses magento 1 internal parser of cron expressions
     *
     * @param mixed $expr
     * @return array|false with five values (zero-indexed) or FALSE in case it does not exist.
     * @throws Mage_Core_Exception
     */
    private function parseCronExpression($expr)
    {
        if ((string) $expr === 'always') {
            return array_fill(0, 5, '*');
        }

        /** @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');

        try {
            $schedule->setCronExpr($expr);
        } catch (Mage_Cron_Exception $mageCronException) {
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
