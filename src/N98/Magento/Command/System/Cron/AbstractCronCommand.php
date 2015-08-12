<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractCronCommand extends AbstractMagentoCommand
{
    /**
     * @return array
     */
    protected  function getJobs()
    {
        $table = array();

        foreach (\Mage::getConfig()->getNode('crontab/jobs')->children() as $job) {
            /* @var $job \Mage_Core_Model_Config_Element */
            $table[] = array('Job'  => (string) $job->getName()) + $this->getSchedule($job);
        }

        usort($table, function($a, $b) {
            return strcmp($a['Job'], $b['Job']);
        });

        return $table;
    }

    /**
     * @param $job
     * @return array
     */
    protected function getSchedule($job)
    {
        $expr = null;

        if (isset($job->schedule->config_path)) {
            $expr = \Mage::getStoreConfig((string) $job->schedule->config_path);
        } elseif (isset($job->schedule->cron_expr)) {
            $expr = (string) $job->schedule->cron_expr;
        }

        if ($expr) {
            if ($expr == 'always') {
                return array('m' => '*', 'h' => '*', 'D' => '*', 'M' => '*', 'WD' => '*');
            }

            $schedule = $this->_getModel('cron/schedule', 'Mage_Cron_Model_Schedule');
            $schedule->setCronExpr($expr);
            $array = $schedule->getCronExprArr();
            return array(
                'm'  => $array[0],
                'h'  => $array[1],
                'D'  => $array[2],
                'M'  => $array[3],
                'WD' => $array[4]
            );
        }

        return array('m' => '  ', 'h' => '  ', 'D' => '  ', 'M' => '  ', 'WD' => '  ');
    }
}
