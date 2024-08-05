<?php

namespace N98\Magento\Command\System\Cron;

use Exception;
use Mage;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Model_Schedule;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Run cronjob command
 *
 * @package N98\Magento\Command\System\Cron
 */
class RunCommand extends AbstractCronCommand
{
    public const REGEX_RUN_MODEL = '#^([a-z0-9_]+/[a-z0-9_]+)::([a-z0-9_]+)$#i';
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
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
If no `job` argument is passed you can select a job from a list.
See it in action: https://www.youtube.com/watch?v=QkzkLgrfNaM
If option schedule is present, cron is not launched, but just scheduled immediately in magento crontab.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $jobCode = $input->getArgument('job');
        if (!$jobCode) {
            $this->writeSection($output, 'Cronjob');
            $jobCode = $this->askJobCode($input, $output, $this->getJobs());
        }

        $runConfigModel = $this->getRunConfigModelByJobCode($jobCode);

        [$callback, $callableName] = $this->getCallbackFromRunConfigModel($runConfigModel, $jobCode);

        $output->write('<info>Run </info><comment>' . $callableName . '</comment> ');

        if ($input->hasOption('schedule') && $input->getOption('schedule')) {
            $this->scheduleConfigModel($callback, $jobCode);
        } else {
            $this->executeConfigModel($callback, $jobCode);
        }

        $output->writeln('<info>done</info>');
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $jobs array of array containing "job" keyed string entries of job-codes
     *
     * @return string         job-code
     * @throws InvalidArgumentException|Exception when user selects invalid job interactively
     */
    protected function askJobCode(InputInterface $input, OutputInterface $output, array $jobs)
    {
        $index = 0;
        $keyMap = array_keys($jobs);

        $choices = [];
        foreach ($jobs as $key => $job) {
            $choices[] = '<comment>' . $job['Job'] . '</comment>';
        }

        $dialog = $this->getQuestionHelper();
        $question = new ChoiceQuestion('<question>Please select job:</question> ', $choices);
        $question->setValidator(function ($typeInput) use ($keyMap, $jobs) {
            $key = $keyMap[$typeInput];
            if (!isset($jobs[$key])) {
                throw new InvalidArgumentException('Invalid job');
            }

            return $jobs[$key]['Job'];
        });

        return $dialog->ask($input, $output, $question);
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
        [, $runModel, $runMethod] = $runMatches;
        unset($runMatches);

        $model = Mage::getModel($runModel);
        if (false === $model) {
            throw new RuntimeException(sprintf('Failed to create new "%s" model for job "%s"', $runModel, $jobCode));
        }
        $callback = [$model, $runMethod];
        $callableName = sprintf("%s::%s", $runModel, $runMethod);
        if (!$model || !is_callable($callback, false, $callableName)) {
            throw new RuntimeException(sprintf('Invalid callback: %s for job "%s"', $callableName, $jobCode));
        }

        return [$callback, $callableName];
    }

    /**
     * @param array $callback
     * @param string $jobCode
     * @throws Exception
     */
    private function executeConfigModel($callback, $jobCode)
    {
        Mage::getConfig()->init()->loadEventObservers('crontab');
        Mage::app()->addEventArea('crontab');

        /* @var Mage_Cron_Model_Schedule $schedule */
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
                ->setScheduledAt($timestamp)
                ->save();

            $callback($schedule);

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
        /* @var Mage_Cron_Model_Schedule $schedule */
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

        /* @var Mage_Core_Model_Config_Element $jobConfig */
        $jobConfig = $jobsRoot->{$jobCode};
        if (!$jobConfig || !$jobConfig->run) {
            $jobConfig = $defaultJobsRoot->{$jobCode};
        }
        if (!$jobConfig || !$jobConfig->run) {
            throw new RuntimeException(sprintf('No job-config found for job "%s"!', $jobCode));
        }

        /* @var Mage_Core_Model_Config_Element $runConfig */
        $runConfig = $jobConfig->run;
        if (empty($runConfig->model)) {
            throw new RuntimeException(sprintf('No run-config found for job "%s"!', $jobCode));
        }

        return (string) $runConfig->model;
    }
}
