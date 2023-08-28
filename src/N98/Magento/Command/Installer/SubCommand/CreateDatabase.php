<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\BinaryString;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateDatabase
 * @package N98\Magento\Command\Installer\SubCommand
 */
class CreateDatabase extends AbstractSubCommand
{
    /**
     * @var array
     */
    private $argv;

    /**
     * @var \Closure
     */
    protected $notEmptyCallback;

    /**
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->notEmptyCallback = function ($input) {
            if (empty($input)) {
                throw new \InvalidArgumentException('Please enter a value');
            }
            return $input;
        };

        $dbOptions = ['--dbHost', '--dbUser', '--dbPass', '--dbName'];
        $dbOptionsFound = 0;
        foreach ($dbOptions as $dbOption) {
            foreach ($this->getCliArguments() as $definedCliOption) {
                if (BinaryString::startsWith($definedCliOption, $dbOption)) {
                    $dbOptionsFound++;
                }
            }
        }

        $hasAllOptions = $dbOptionsFound === 4;

        // if all database options were passed in at cmd line
        if ($hasAllOptions) {
            $this->config->setString('db_host', $this->input->getOption('dbHost'));
            $this->config->setString('db_user', $this->input->getOption('dbUser'));
            $this->config->setString('db_pass', $this->input->getOption('dbPass'));
            $this->config->setString('db_name', $this->input->getOption('dbName'));
            $this->config->setInt('db_port', (int) $this->input->getOption('dbPort'));
            $db = $this->validateDatabaseSettings($this->input, $this->output);

            if ($db === false) {
                throw new \InvalidArgumentException('Database configuration is invalid');
            }
        } else {
            /** @var $questionHelper QuestionHelper */
            $questionHelper = $this->getCommand()->getHelperSet()->get('question');
            do {
                // Host
                $dbHostDefault = $this->input->getOption('dbHost') ?
                    $this->input->getOption('dbHost') : $this->commandConfig['installation']['db']['host'];

                $question = new Question(
                    '<question>Please enter the database host</question> <comment>[' . $dbHostDefault . ']</comment>: ',
                    $dbHostDefault
                );
                $question->setValidator($this->notEmptyCallback);

                $this->config->setString(
                    'db_host',
                    $questionHelper->ask(
                        $this->input,
                        $this->output,
                        $question
                    )
                );

                // Port
                $dbPortDefault = $this->input->getOption('dbPort') ?
                    $this->input->getOption('dbPort') : $this->commandConfig['installation']['db']['port'];

                $question = new Question(
                    sprintf(
                        '<question>Please enter the database port </question> <comment>[%s]</comment>: ',
                        $dbPortDefault
                    ),
                    $dbPortDefault
                );
                $question->setValidator($this->notEmptyCallback);

                $this->config->setInt(
                    'db_port',
                    (int) $questionHelper->ask(
                        $this->input,
                        $this->output,
                        $question
                    )
                );

                // User
                $dbUserDefault = $this->input->getOption('dbUser') ?
                    $this->input->getOption('dbUser') : $this->commandConfig['installation']['db']['user'];

                $question = new Question(
                    sprintf(
                        '<question>Please enter the database username</question> <comment>[%s]</comment>: ',
                        $dbUserDefault
                    ),
                    $dbUserDefault
                );
                $question->setValidator($this->notEmptyCallback);

                $this->config->setString(
                    'db_user',
                    $questionHelper->ask(
                        $this->input,
                        $this->output,
                        $question
                    )
                );

                // Password
                $dbPassDefault = $this->input->getOption('dbPass') ?
                    $this->input->getOption('dbPass') : $this->commandConfig['installation']['db']['pass'];

                $question = new Question(
                    sprintf(
                        '<question>Please enter the database password</question> <comment>[%s]</comment>: ',
                        $dbPassDefault
                    ),
                    $dbPassDefault
                );

                $this->config->setString(
                    'db_pass',
                    $questionHelper->ask(
                        $this->input,
                        $this->output,
                        $question
                    )
                );

                // DB-Name
                $dbNameDefault = $this->input->getOption('dbName') ?
                    $this->input->getOption('dbName') : $this->commandConfig['installation']['db']['name'];

                $question = new Question(
                    sprintf(
                        '<question>Please enter the database name</question> <comment>[%s]</comment>: ',
                        $dbNameDefault
                    ),
                    $dbNameDefault
                );
                $question->setValidator($this->notEmptyCallback);

                $this->config->setString(
                    'db_name',
                    $questionHelper->ask(
                        $this->input,
                        $this->output,
                        $question
                    )
                );

                $db = $this->validateDatabaseSettings($this->input, $this->output);
            } while ($db === false);
        }

        $this->config->setObject('db', $db);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|\PDO
     */
    protected function validateDatabaseSettings(InputInterface $input, OutputInterface $output)
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s',
                $this->config->getString('db_host'),
                $this->config->getString('db_port')
            );

            $db = new \PDO($dsn, $this->config->getString('db_user'), $this->config->getString('db_pass'));

            $dbName = $this->config->getString('db_name');
            if (!$db->query('USE `' . $dbName . '`')) {
                $db->query('CREATE DATABASE `' . $dbName . '`');
                $output->writeln('<info>Created database ' . $dbName . '</info>');
                $db->query('USE `' . $dbName . '`');

                // Check DB version
                $statement = $db->query('SELECT VERSION()');
                $mysqlVersion = $statement->fetchColumn(0);
                if (version_compare($mysqlVersion, '5.6.0', '<')) {
                    throw new \Exception('MySQL Version must be >= 5.6.0');
                }

                return $db;
            }

            if ($input->getOption('noDownload') && !$input->getOption('forceUseDb')) {
                $output->writeln("<error>Database {$this->config->getString('db_name')} already exists.</error>");

                return false;
            }

            return $db;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return false;
    }

    /**
     * @return array
     */
    private function getCliArguments()
    {
        if ($this->argv === null) {
            $this->argv = $_SERVER['argv'];
        }

        return $this->argv;
    }
}
