<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Maintain;

use InvalidArgumentException;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database check command
 *
 * @package N98\Magento\Command\Database\Maintain
 */
class CheckTablesCommand extends AbstractCommand implements CommandDataInterface
{
    public const COMMAND_OPTION_TYPE = 'type';

    public const COMMAND_OPTION_REPAIR = 'repair';

    public const COMMAND_OPTION_TABLE = 'table';

    public const MESSAGE_CHECK_NOT_SUPPORTED = 'The storage engine for the table doesn\'t support check';

    public const MESSAGE_REPAIR_NOT_SUPPORTED = 'The storage engine for the table doesn\'t support repair';

    /**
     * @var string
     */
    protected static $defaultName = 'db:maintain:check-tables';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Check database tables.';

    /**
     * @var bool
     */
    protected bool $showProgress = false;

    /**
     * @var array<int, string>
     */
    protected array $allowedTypes = ['QUICK', 'FAST', 'CHANGED', 'MEDIUM', 'EXTENDED'];

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_TYPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Check type (one of QUICK, FAST, MEDIUM, EXTENDED, CHANGED)',
                'MEDIUM'
            )
            ->addOption(
                self::COMMAND_OPTION_REPAIR,
                null,
                InputOption::VALUE_NONE,
                'Repair tables (only MyISAM)'
            )
            ->addOption(
                self::COMMAND_OPTION_TABLE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Process only given table (wildcards are supported)'
            );

        parent::configure();
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
<comment>TYPE OPTIONS</comment>

<info>QUICK</info>
            Do not scan the rows to check for incorrect links.
            Applies to InnoDB and MyISAM tables and views.
<info>FAST</info>
            Check only tables that have not been closed properly.
            Applies only to MyISAM tables and views; ignored for InnoDB.
<info>CHANGED</info>
            Check only tables that have been changed since the last check or that
            have not been closed properly. Applies only to MyISAM tables and views;
            ignored for InnoDB.
<info>MEDIUM</info>
            Scan rows to verify that deleted links are valid.
            This also calculates a key checksum for the rows and verifies this with a
            calculated checksum for the keys. Applies only to MyISAM tables and views;
            ignored for InnoDB.
<info>EXTENDED</info>
            Do a full key lookup for all keys for each row. This ensures that the table
            is 100% consistent, but takes a long time.
            Applies only to MyISAM tables and views; ignored for InnoDB.

<comment>InnoDB</comment>
            InnoDB tables will be optimized with the ALTER TABLE ... ENGINE=InnoDB statement.
            The options above do not apply to them.
HELP;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function isTypeAllowed(InputInterface $input): void
    {
        /** @var string $type */
        $type = $input->getOption(self::COMMAND_OPTION_TYPE);
        $type = strtoupper($type);
        if ($type && !in_array($type, $this->allowedTypes)) {
            throw new InvalidArgumentException('Invalid type was given');
        }
    }

    /**
     * @param ProgressBar $progress
     */
    protected function progressAdvance(ProgressBar $progress): void
    {
        if ($this->showProgress) {
            $progress->advance();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @uses self::_checkInnoDB()
     * @uses self::_checkMEMORY()
     * @uses self::_checkMyISAM()
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $data = [];
        $this->isTypeAllowed($input);
        $database = $this->getDatabaseHelper();
        $this->showProgress = $input->getOption(self::COMMAND_OPTION_FORMAT) === null;

        /** @var string $table */
        $table = $input->getOption(self::COMMAND_OPTION_TABLE);
        if ($table) {
            $resolvedTables = [$database->resolveTables(
                ['@check'],
                ['check' => ['tables' => explode(' ', $table)]]
            )];
            $tables = $resolvedTables[0];
        } else {
            $tables = $database->getTables();
        }

        $allTableStatus = $database->getTablesStatus();

        $progress = new ProgressBar($output, 50);

        if ($this->showProgress) {
            $progress->start(count($tables));
        }

        $methods = ['InnoDB' => 1, 'MEMORY' => 1, 'MyISAM' => 1];

        foreach ($tables as $tableName) {
            if (isset($allTableStatus[$tableName]) && isset($methods[$allTableStatus[$tableName]['Engine']])) {
                $m = '_check' . $allTableStatus[$tableName]['Engine'];
                $data = array_merge($data, $this->$m($input, $output, $tableName));
            } else {
                $data[] = [
                    'Table'     => $tableName,
                    'Operation' => 'not supported',
                    'Type'      => '',
                    'Status'    => ''
                ];
            }
            $this->progressAdvance($progress);
        }

        if ($this->showProgress) {
            $progress->finish();
            $output->writeln('');
        }

        $this->data = $data;
    }

    /**
     * @param OutputInterface $output
     * @param string $tableName
     * @param string $engine
     * @return array<int, array<string, string>>
     */
    protected function _queryAlterTable(OutputInterface $output, string $tableName, string $engine): array
    {
        $connection = $this->getDatabaseHelper()->getConnection($output);
        $start = microtime(true);
        $affectedRows = $connection->exec(sprintf('ALTER TABLE %s ENGINE=%s', $tableName, $engine));

        return [[
            'Table'     => $tableName,
            'Operation' => 'ENGINE ' . $engine,
            'Type'      => sprintf('%15s rows', (string) $affectedRows),
            'Status'    => sprintf('%.3f secs', microtime(true) - $start)
        ]];
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $tableName
     * @return array<int, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    protected function _checkInnoDB(InputInterface $input, OutputInterface $output, string $tableName): array
    {
        return $this->_queryAlterTable($output, $tableName, 'InnoDB');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $tableName
     * @return array<int, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    protected function _checkMEMORY(InputInterface $input, OutputInterface $output, string $tableName): array
    {
        return $this->_queryAlterTable($output, $tableName, 'MEMORY');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $tableName
     * @return array<int, array<string, string>>
     */
    protected function _checkMyISAM(InputInterface $input, OutputInterface $output, string $tableName): array
    {
        $table = [];

        /** @var string $type */
        $type = $input->getOption(self::COMMAND_OPTION_TYPE);

        /** @var array<string, string> $result */
        $result = $this->_query($output, sprintf('CHECK TABLE %s %s', $tableName, $type));
        if ($result['Msg_text'] == self::MESSAGE_CHECK_NOT_SUPPORTED) {
            return [];
        }

        $table[] = [
            'Table'     => $tableName,
            'Operation' => $result['Op'],
            'Type'      => $type,
            'Status'    => $result['Msg_text']
        ];

        if ($result['Msg_text'] != 'OK' && $input->getOption(self::COMMAND_OPTION_REPAIR)) {
            /** @var array<string, string> $result */
            $result = $this->_query($output, sprintf('REPAIR TABLE %s %s', $tableName, $type));
            if ($result['Msg_text'] != self::MESSAGE_REPAIR_NOT_SUPPORTED) {
                $table[] = [
                    'Table'     => $tableName,
                    'Operation' => $result['Op'],
                    'Type'      => $type,
                    'Status'    => $result['Msg_text']
                ];
            }
        }

        return $table;
    }

    /**
     * @param OutputInterface $output
     * @param string $sql
     * @return mixed
     */
    protected function _query(OutputInterface $output, string $sql)
    {
        $connection = $this->getDatabaseHelper()->getConnection($output);
        $query = $connection->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Skip initialisation
     *
     * @param bool $soft
     */
    public function initMagento(bool $soft = false): void
    {
    }
}
