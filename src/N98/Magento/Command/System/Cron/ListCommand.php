<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:cron:list')
            ->setAliases(array('system:cron:list'))
            ->addDeprecatedAlias('system:cron:list', 'Please use sys:cron:list')
            ->setDescription('Lists all cronjobs');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Cronjob List');
        $this->initMagento();

        foreach (\Mage::getConfig()->getNode('crontab/jobs')->children() as $job) {
            $table[(string) $job->getName()] = array('Job'  => (string) $job->getName()) + $this->getSchedule($job);
        }

        ksort($table);
        $this->getHelper('table')->write($output, $table);
    }

    /**
     * @param $job
     * @return array
     */
    protected function getSchedule($job)
    {
        $expr = (string) $job->schedule->cron_expr;
        if ($expr) {
            $schedule = \Mage::getModel('cron/schedule');
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