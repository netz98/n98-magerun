<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractMagentoCommand
{
    const REGEX_RUN_MODEL = '#^([a-z0-9_]+/[a-z0-9_]+)::([a-z0-9_]+)$#i';

    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:cron:run')
            ->addArgument('job', InputArgument::REQUIRED, 'Job code')
            ->setDescription('Runs a cronjob by job code');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $jobCode = $input->getArgument('job');

            $jobsRoot = \Mage::getConfig()->getNode('crontab/jobs');
            $defaultJobsRoot = \Mage::getConfig()->getNode('default/crontab/jobs');

            $jobConfig = $jobsRoot->{$jobCode};
            if (!$jobConfig || !$jobConfig->run) {
                $jobConfig = $defaultJobsRoot->{$jobCode};
                if (!$jobConfig || !$jobConfig->run) {
                    throw new \Exception('No job config found!');
                }
            }

            $runConfig = $jobConfig->run;

            if ($runConfig->model) {

                if (!preg_match(self::REGEX_RUN_MODEL, (string)$runConfig->model, $run)) {
                    throw new \Exception('Invalid model/method definition, expecting "model/class::method".');
                }
                if (!($model = \Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
                    throw new \Exception('Invalid callback: %s::%s does not exist', $run[1], $run[2]);
                }
                $callback = array($model, $run[2]);

                $schedule = \Mage::getModel('cron/schedule');
                $schedule->trySchedule(time());
                $schedule->tryLockJob();

                $output->write('<info>Run </info><comment>' . get_class($model) . '::' . $run[2] . '</comment> ');

                $schedule
                    ->setStatus(\Mage_Cron_Model_Schedule::STATUS_RUNNING)
                    ->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                    ->save();

                call_user_func_array($callback, array($schedule));

                $schedule
                    ->setStatus(\Mage_Cron_Model_Schedule::STATUS_SUCCESS)
                    ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                    ->save();

                $output->writeln('<info>done</info>');
            }
            if (empty($callback)) {
                Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
            }
        }
    }
}