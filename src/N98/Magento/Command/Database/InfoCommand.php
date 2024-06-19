<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use N98\Magento\DbSettings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database information command
 *
 * @package N98\Magento\Command\Database
 */
class InfoCommand extends AbstractDatabaseCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Database information';

    public const COMMAND_ARGUMENT_SETTING = 'setting';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'db:info';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Dumps database information.';

    protected function configure(): void
    {
        $this
            ->addDeprecatedAlias(
                'database:info',
                'Please use db:info'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_SETTING,
                InputArgument::OPTIONAL,
                'Only output value of named setting'
            )
        ;

        parent::configure();
    }

    public function getHelp(): string
    {
        return <<<HELP
This command is useful to print all information about the current configured database in app/etc/local.xml.
It can print connection string for JDBC, PDO connections.
HELP;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws InvalidArgumentException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (($settingArgument = $input->getArgument(self::COMMAND_ARGUMENT_SETTING)) !== null) {
            $settings = $this->getData($input, $output);
            if (!isset($settings[$settingArgument])) {
                throw new InvalidArgumentException('Unknown setting: ' . $settingArgument);
            }
            $output->writeln((string) $settings[$settingArgument]['Value']);

            return Command::SUCCESS;
        }

        return parent::execute($input, $output);
    }

    /**
     * {@inheritdoc}
     * @return array<string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->detectDbSettings($output);

            $settings = [];
            foreach ($this->dbSettings as $key => $value) {
                $settings[$key] = (string) $value;
            }

            if ($this->dbSettings instanceof DbSettings) {
                $isSocketConnect = $this->dbSettings->isSocketConnect();
            } else {
                $isSocketConnect = false;
            }

            // note: there is no need to specify the default port neither for PDO, nor JDBC nor CLI.
            $portOrDefault = $this->dbSettings['port'] ?? 3306;

            $pdoConnectionString = '';
            if ($isSocketConnect) {
                $pdoConnectionString = sprintf(
                    'mysql:unix_socket=%s;dbname=%s',
                    $this->dbSettings['unix_socket'],
                    $this->dbSettings['dbname']
                );
            } else {
                $pdoConnectionString = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s',
                    $this->dbSettings['host'],
                    $portOrDefault,
                    $this->dbSettings['dbname']
                );
            }
            $settings['PDO-Connection-String'] = $pdoConnectionString;

            $jdbcConnectionString = '';
            if ($isSocketConnect) {
                // isn't supported according to this post: http://stackoverflow.com/a/18493673/145829
                $jdbcConnectionString = 'Connecting using JDBC through a unix socket isn\'t supported!';
            } else {
                $jdbcConnectionString = sprintf(
                    'jdbc:mysql://%s:%s/%s?username=%s&password=%s',
                    $this->dbSettings['host'],
                    $portOrDefault,
                    $this->dbSettings['dbname'],
                    $this->dbSettings['username'],
                    $this->dbSettings['password']
                );
            }
            $settings['JDBC-Connection-String'] = $jdbcConnectionString;

            $database = $this->getDatabaseHelper();
            $mysqlCliString = 'mysql ' . $database->getMysqlClientToolConnectionString();
            $settings['MySQL-Cli-String'] = $mysqlCliString;

            foreach ($settings as $settingName => $settingValue) {
                $this->data[$settingName] = [
                    'Name' => $settingName,
                    'Value' => $settingValue
                ];
            }
        }

        return $this->data;
    }
}
