<?php

namespace N98\Magento\Command\System\Cron;

use Exception;
use Mage;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Model_Schedule;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class RunCommand extends AbstractCronCommand
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
            ->addArgument('job', InputArgument::OPTIONAL, 'Job code')
            ->addOption('schedule', 's', InputOption::VALUE_NONE, 'Schedule cron instead of run with current user')
            ->setDescription('Runs a cronjob by job code');
        $help = <<<HELP
If no `job` argument is passed you can select a job from a list.
See it in action: http://www.youtube.com/watch?v=QkzkLgrfNaM
If option schedule is present, cron is not launched, but just scheduled immediately in magento crontab.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $jobCode = $input->getArgument('job');
        if (!$jobCode) {
            $this->writeSection($output, 'Cronjob');
            $jobCode = $this->askJobCode($output, $this->getJobs());
        }

        $runConfigModel = $this->getRunConfigModelByJobCode($jobCode);

        list($callback, $callableName) = $this->getCallbackFromRunConfigModel($runConfigModel, $jobCode);

        $output->write('<info>Run </info><comment>' . $callableName . '</comment> ');

        if ($input->hasOption('schedule') && $input->getOption('schedule')) {
            $this->scheduleConfigModel($callback, $jobCode);
        } else {
            $this->executeConfigModel($callback, $jobCode);
        }

        $output->writeln('<info>done</info>');
    }

    /**
     * @param OutputInterface $output
     * @param array $jobs array of array containing "job" keyed string entries of job-codes
     *
     * @return string         job-code
     * @throws InvalidArgumentException when user selects invalid job interactively
     */
    protected function askJobCode(OutputInterface $output, array $jobs)
    {
        $index = 0;
        $keyMap = array_keys($jobs);
        $question = array();

        foreach ($jobs as $key => $job) {
            $question[] = '<comment>[' . ($index++) . ']</comment> ' . $job['Job'] . PHP_EOL;
        }
        $question[] = '<question>Please select job: </question>' . PHP_EOL;

        /** @var $dialogHelper DialogHelper */
        $dialogHelper = $this->getHelper('dialog');
        $jobCode = $dialogHelper->askAndValidate(
            $output,
            $question,
            function ($typeInput) use ($keyMap, $jobs) {
                $key = $keyMap[$typeInput];
                if (!isset($jobs[$key])) {
                    throw new InvalidArgumentException('Invalid job');
                }

                return $jobs[$key]['Job'];
            }
        );

        return $jobCode;
    }

    /**
     * @param string $runConfigModel
     * @param string $jobCode
     * @return array
     */
    private function getCallbackFromRunConfigModel($runConfigModel, $jobCode)
    {
        if (!preg_match(self::REGEX_RUN_MODEL, $runConfigModel, $runMatches)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid model/method definition "%s" for job "%s", expecting "model/class::method".',
                    $runConfigModel,
                    $jobCode
                )
            );
        }
        list(, $runModel, $runMethod) = $runMatches;
        unset($runMatches);

        $model = Mage::getModel($runModel);
        if (false === $model) {
            throw new RuntimeException(sprintf('Failed to create new "%s" model for job "%s"', $runModel, $jobCode));
        }
        $callback = array($model, $runMethod);
        $callableName = sprintf("%s::%s", $runModel, $runMethod);
        if (!$model || !is_callable($callback, false, $callableName)) {
            throw new RuntimeException(sprintf('Invalid callback: %s for job "%s"', $callableName, $jobCode));
        }

        return array($callback, $callableName);
    }

    /**
     * @param array $callback
     * @param string $jobCode
     */
    private function executeConfigModel($callback, $jobCode)
    {
        Mage::getConfig()->init()->loadEventObservers('crontab');
        Mage::app()->addEventArea('crontab');

        /* @var $schedule Mage_Cron_Model_Schedule */
        $schedule = Mage::getModel('cron/schedule');
        if (false === $schedule) {
            throw new RuntimeException('Failed to create new Mage_Cron_Model_Schedule model');
        }

        $environment = new ServerEnvironment();
        $environment->initalize();

        try {
            $timestamp = strftime('%Y-%m-%d %H:%M:%S', time());
            $schedule
                ->setJobCode($jobCode)
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
                ->setCreatedAt($timestamp)
                ->setExecutedAt($timestamp)
                ->save();

            call_user_func_array($callback, array($schedule));

            $schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_SUCCESS);
        } catch (Exception $cronException) {
            $schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
        }

        $schedule->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))->save();

        if (isset($cronException)) {
            throw new RuntimeException(
                sprintf('Cron-job "%s" threw exception %s', $jobCode, get_class($cronException)),
                0,
                $cronException
            );
        }

        if (empty($callback)) {
            Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
        }
    }

    /**
     * @param array $callback
     * @param string $jobCode
     */
    private function scheduleConfigModel($callback, $jobCode)
    {
        /* @var $schedule Mage_Cron_Model_Schedule */
        $schedule = Mage::getModel('cron/schedule');
        if (false === $schedule) {
            throw new RuntimeException('Failed to create new Mage_Cron_Model_Schedule model');
        }

        if (empty($callback)) {
            Mage::throwException(Mage::helper('cron')->__('No callbacks found'));
        }

        try {
            $timestamp = strftime('%Y-%m-%d %H:%M:%S', time());
            $schedule
                ->setJobCode($jobCode)
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->setCreatedAt($timestamp)
                ->setScheduledAt($timestamp)
                ->save();
        } catch (Exception $cronException) {
            throw new RuntimeException(
                sprintf('Cron-job "%s" threw exception %s', $jobCode, get_class($cronException)),
                0,
                $cronException
            );
        }
    }

    /**
     * @param $jobCode
     * @return string
     */
    private function getRunConfigModelByJobCode($jobCode)
    {
        $jobsRoot = Mage::getConfig()->getNode('crontab/jobs');
        $defaultJobsRoot = Mage::getConfig()->getNode('default/crontab/jobs');

        /* @var $jobConfig Mage_Core_Model_Config_Element */
        $jobConfig = $jobsRoot->{$jobCode};
        if (!$jobConfig || !$jobConfig->run) {
            $jobConfig = $defaultJobsRoot->{$jobCode};
        }
        if (!$jobConfig || !$jobConfig->run) {
            throw new RuntimeException(sprintf('No job-config found for job "%s"!', $jobCode));
        }

        /* @var $runConfig Mage_Core_Model_Config_Element */
        $runConfig = $jobConfig->run;
        if (empty($runConfig->model)) {
            throw new RuntimeException(sprintf('No run-config found for job "%s"!', $jobCode));
        }

        $runConfigModel = (string) $runConfig->model;

        return $runConfigModel;
    }
}
