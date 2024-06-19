<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Exception;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Abstract;
use Mage_Core_Model_Config_Element;
use Mage_Cron_Model_Schedule;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Throwable;

/**
 * Cron run command
 *
 * @package N98\Magento\Command\Cron
 */
class RunCommand extends AbstractCronCommand
{
    public const REGEX_RUN_MODEL = '#^([a-z0-9_]+/[a-z0-9_]+)::([a-z0-9_]+)$#i';

    public const COMMAND_ARGUMENT_JOB = 'job';

    public const COMMAND_OPTION_SCHEDULE = 'schedule';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:cron:run';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Runs a cronjob by job code.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_JOB,
                InputArgument::OPTIONAL,
                'Job code'
            )
            ->addOption(
                self::COMMAND_OPTION_SCHEDULE,
                's',
                InputOption::VALUE_NONE,
                'Schedule cron instead of run with current user'
            )
        ;
    }

    /**
     * @return string
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
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        /** @var string $jobCode */
        $jobCode = $input->getArgument(self::COMMAND_ARGUMENT_JOB);
        if (!$jobCode) {
            $this->writeSection($output, 'Cronjob');
            /** @var string $jobCode */
            $jobCode = $this->askJobCode($input, $output, $this->getJobs());
        }

        $runConfigModel = $this->getRunConfigModelByJobCode($jobCode);

        [$callback, $callableName] = $this->getCallbackFromRunConfigModel($runConfigModel, $jobCode);

        $output->write('<info>Run </info><comment>' . $callableName . '</comment> ');

        if ($input->hasOption(self::COMMAND_OPTION_SCHEDULE) && $input->getOption(self::COMMAND_OPTION_SCHEDULE)) {
            $this->scheduleConfigModel($callback, $jobCode);
        } else {
            $this->executeConfigModel($callback, $jobCode);
        }

        $output->writeln('<info>done</info>');

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array<string, array<string, Mage_Core_Model_Config_Element|string|null>> $jobs
     *  array of array containing "job" keyed string entries of job-codes
     *
     * @return mixed         job-code
     * @throws InvalidArgumentException|Exception when user selects invalid job interactively
     */
    protected function askJobCode(InputInterface $input, OutputInterface $output, array $jobs)
    {
        $keyMap = array_keys($jobs);

        $choices = [];
        foreach ($jobs as $job) {
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
     * @return array{array{Mage_Core_Model_Abstract, string}&callable(): mixed, callable-string}
     */
    private function getCallbackFromRunConfigModel(string $runConfigModel, string $jobCode): array
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
     * @param mixed $callback
     * @param string $jobCode
     * @throws Exception|Throwable
     */
    private function executeConfigModel($callback, string  $jobCode): void
    {
        $this->_getMageConfig()->init()->loadEventObservers('crontab');
        Mage::app()->addEventArea('crontab');

        /* @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');

        $environment = new ServerEnvironment();
        $environment->initialize();

        try {
            $timestamp = strftime('%Y-%m-%d %H:%M:%S', time());
            $schedule
                ->setJobCode($jobCode)
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
                ->setCreatedAt($timestamp)
                ->setExecutedAt($timestamp)
                ->setScheduledAt($timestamp)
                ->save();

            if (is_callable($callback)) {
                $callback($schedule);
            }

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
     * @param mixed $callback
     * @param string $jobCode
     * @throws Mage_Core_Exception
     */
    private function scheduleConfigModel($callback, string $jobCode): void
    {
        /* @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');

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
        } catch (Throwable $e) {
        }
    }

    /**
     * @param string $jobCode
     * @return string
     */
    private function getRunConfigModelByJobCode(string$jobCode): string
    {
        $jobsRoot = $this->_getMageConfigNode('crontab/jobs');
        $defaultJobsRoot = $this->_getMageConfigNode('default/crontab/jobs');

        $jobConfig = $jobsRoot->{$jobCode};
        if (!$jobConfig || !$jobConfig->run) {
            $jobConfig = $defaultJobsRoot->{$jobCode};
        }
        if (!$jobConfig || !$jobConfig->run) {
            throw new RuntimeException(sprintf('No job-config found for job "%s"!', $jobCode));
        }

        $runConfig = $jobConfig->run;
        if (empty($runConfig->model)) {
            throw new RuntimeException(sprintf('No run-config found for job "%s"!', $jobCode));
        }

        return (string) $runConfig->model;
    }
}
