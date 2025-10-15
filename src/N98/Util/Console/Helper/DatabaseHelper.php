<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use InvalidArgumentException;
use N98\Magento\Application;
use N98\Magento\DbSettings;
use PDO;
use PDOStatement;
use RuntimeException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseHelper
 *
 * @package N98\Util\Console\Helper
 */
class DatabaseHelper extends AbstractHelper
{
    protected ?DbSettings $dbSettings = null;

    /**
     * @deprecated since 1.97.9, use $dbSettings->isSocketConnect()
     */
    protected bool $isSocketConnect = false;

    protected ?PDO $_connection = null;

    protected ?array $_tables = null;

    public function detectDbSettings(OutputInterface $output, ?string $connectionNode = null): void
    {
        if (!is_null($this->dbSettings)) {
            return;
        }

        $baseApplication = $this->getApplication();
        if (!$baseApplication instanceof Application) {
            return;
        }

        $baseApplication->detectMagento();

        $configFile = $baseApplication->getMagentoRootFolder() . '/app/etc/local.xml';

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(
                sprintf('<debug>Loading database configuration from file <info>%s</info></debug>', $configFile),
            );
        }

        try {
            $this->dbSettings = new DbSettings($configFile, $connectionNode);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $output->writeln('<error>' . $invalidArgumentException->getMessage() . '</error>');
            throw new RuntimeException('Failed to load database settings from config file', 0, $invalidArgumentException);
        }
    }

    /**
     * Connects to the database without initializing magento
     */
    public function getConnection(?OutputInterface $output = null): PDO
    {
        if (!$this->_connection instanceof \PDO) {
            $this->_connection = $this->getDbSettings($output)->getConnection();
        }

        return $this->_connection;
    }

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @see Zend_Db_Adapter_Pdo_Abstract
     */
    public function dsn(): string
    {
        return $this->getDbSettings()->getDsn();
    }

    /**
     * Check whether current mysql user has $privilege privilege
     */
    public function mysqlUserHasPrivilege(string $privilege): bool
    {
        $statement = $this->getConnection()->query('SHOW GRANTS');
        if (!$statement) {
            return false;
        }

        $result = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (!$result) {
            return false;
        }

        foreach ($result as $row) {
            if (preg_match('/^GRANT(.*)' . strtoupper($privilege) . '/', $row)
                || preg_match('/^GRANT(.*)ALL/', $row)
            ) {
                return true;
            }
        }

        return false;
    }

    public function getMysqlClientToolConnectionString(): string
    {
        return $this->getDbSettings()->getMysqlClientToolConnectionString();
    }

    /**
     * Get mysql variable value
     *
     * @return false|array returns array on success, false on failure
     */
    public function getMysqlVariableValue(string $variable)
    {
        $statement = $this->getConnection()->query(sprintf('SELECT @@%s;', $variable));
        if (false === $statement) {
            throw new RuntimeException(sprintf('Failed to query mysql variable %s', var_export($variable, true)));
        }

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * obtain mysql variable value from the database connection.
     *
     * in difference to @param string $name mysql variable name
     * @param string|null $type [optional] variable type, can be a system variable ("@@", default) or a session variable
     *                     ("@").
          *
     * @return int|string|false|null variable value, null if variable was not defined
     * @throws RuntimeException in case a system variable is unknown (SQLSTATE[HY000]: 1193: Unknown system variable
     *                          'nonexistent')
     * @see getMysqlVariableValue(), this method allows to specify the type of the variable as well
     * as to use any variable identifier even such that need quoting.
     *
     */
    public function getMysqlVariable(string $name, ?string $type = null)
    {
        $type = is_null($type) ? '@@' : $type;

        if (!in_array($type, ['@@', '@'], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid mysql variable type "%s", must be "@@" (system) or "@" (session)', $type),
            );
        }

        $quoted = '`' . strtr($name, ['`' => '``']) . '`';
        $query = sprintf('SELECT %s%s;', $type, $quoted);

        $pdo = $this->getConnection();
        $statement = $pdo->query($query, PDO::FETCH_COLUMN, 0);
        if ($statement instanceof PDOStatement) {
            $result = $statement->fetchColumn(0);
        } else {
            $reason = $pdo->errorInfo()
                ? vsprintf('SQLSTATE[%s]: %s: %s', $pdo->errorInfo())
                : 'no error info';

            throw new RuntimeException(
                sprintf('Failed to query mysql variable %s: %s', var_export($name, true), $reason),
            );
        }

        return $result;
    }

    /**
     * @throws RuntimeException
     * @param array<string, mixed> $commandConfig
     */
    public function getTableDefinitions(array $commandConfig): array
    {
        $tableDefinitions = [];
        if (!isset($commandConfig['table-groups'])) {
            return $tableDefinitions;
        }

        $tableGroups = $commandConfig['table-groups'];
        foreach ($tableGroups as $index => $definition) {
            if (!isset($definition['id'])) {
                throw new RuntimeException('Invalid definition of table-groups (id missing) at index: ' . $index);
            }

            $id = $definition['id'];
            if (isset($tableDefinitions[$id])) {
                throw new RuntimeException('Invalid definition of table-groups (duplicate id) id: ' . $id);
            }

            if (!isset($definition['tables'])) {
                throw new RuntimeException('Invalid definition of table-groups (tables missing) id: ' . $id);
            }

            $tables = $definition['tables'];

            if (is_string($tables)) {
                $tables = preg_split('~\s+~', $tables, -1, PREG_SPLIT_NO_EMPTY);
            }

            if (!is_array($tables)) {
                throw new RuntimeException('Invalid tables definition of table-groups id: ' . $id);
            }

            $tables = array_map('trim', $tables);

            $description = $definition['description'] ?? '';

            $tableDefinitions[$id] = [
                'tables'      => $tables,
                'description' => $description,
            ];
        }

        return $tableDefinitions;
    }

    /**
     * @param array $list to resolve
     * @param array<string, mixed> $definitions from to resolve
     * @param array<string, mixed> $resolved Which definitions where already resolved -> prevent endless loops
     *
     * @throws RuntimeException
     */
    public function resolveTables(array $list, array $definitions = [], array $resolved = []): array
    {
        if (is_null($this->_tables)) {
            $this->_tables = (array) $this->getTables(true);
        }

        $resolvedList = [];
        foreach ($list as $entry) {
            if (substr($entry, 0, 1) === '@') {
                $code = substr($entry, 1);
                if (!isset($definitions[$code])) {
                    throw new RuntimeException('Table-groups could not be resolved: ' . $entry);
                }

                if (!isset($resolved[$code])) {
                    $resolved[$code] = true;
                    $tables = $this->resolveTables(
                        $this->resolveRetrieveDefinitionsTablesByCode($definitions, $code),
                        $definitions,
                        $resolved,
                    );
                    $resolvedList = array_merge($resolvedList, $tables);
                }

                continue;
            }

            // resolve wildcards
            if (strpos($entry, '*') !== false || strpos($entry, '?') !== false) {
                $connection = $this->getConnection();
                $sth = $connection->prepare(
                    'SHOW TABLES LIKE :like',
                    [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
                );
                $entry = str_replace('_', '\\_', $entry);
                $entry = str_replace('*', '%', $entry);
                $entry = str_replace('?', '_', $entry);
                $sth->execute(
                    [':like' => $this->dbSettings['prefix'] . $entry],
                );
                $rows = $sth->fetchAll();
                if ($rows) {
                    foreach ($rows as $row) {
                        $resolvedList[] = $row[0];
                    }
                }

                continue;
            }

            if ($this->_tables && in_array($entry, $this->_tables)) {
                $resolvedList[] = $this->dbSettings['prefix'] . $entry;
            }
        }

        asort($resolvedList);

        return array_unique($resolvedList);
    }

    /**
     * @param array<string, mixed> $definitions
     */
    private function resolveRetrieveDefinitionsTablesByCode(array $definitions, string $code): array
    {
        $tables = $definitions[$code]['tables'];

        if (is_string($tables)) {
            $tables = preg_split('~\s+~', $tables, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!is_array($tables)) {
            throw new RuntimeException('Invalid tables definition of table-groups code: @' . $code);
        }

        return array_reduce($tables, [$this, 'resolveTablesArray'], null);
    }

    /**
     * @param array|null $carry [optional]
     * @param array|string $item [optional]
     * @throws InvalidArgumentException if item is not an array or string
     */
    private function resolveTablesArray(?array $carry = null, $item = null): array
    {
        if (is_string($item)) {
            $item = preg_split('~\s+~', $item, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (is_array($item)) {
            if (count($item) > 1) {
                $item = array_reduce($item, [$this, 'resolveTablesArray'], (array) $carry);
            }
        } else {
            throw new InvalidArgumentException(sprintf('Unable to handle %s', var_export($item, true)));
        }

        return array_merge((array) $carry, $item);
    }

    /**
     * Get list of database tables
     *
     * @param bool $withoutPrefix [optional] remove prefix from the returned table names. prefix is obtained from
     *                            magento database configuration. defaults to false.
     * @return array|false
     * @throws RuntimeException
     */
    public function getTables(?bool $withoutPrefix = false)
    {
        $pdo = $this->getConnection();
        $prefix = $this->dbSettings['prefix'];
        $prefixLength = strlen($prefix);
        $column = 'table_name';
        $columnName = 'table_name';

        $input = [];

        if ($withoutPrefix && $prefixLength) {
            $column = sprintf('SUBSTRING(%1$s FROM 1 + CHAR_LENGTH(:name)) %1$s', $columnName);
            $input[':name'] = $prefix;
        }

        $condition = 'table_schema = database()';

        if ($prefixLength !== 0) {
            $escape = '=';
            $condition .= sprintf(" AND %s LIKE :like ESCAPE '%s'", $columnName, $escape);
            $input[':like'] = $this->quoteLike($prefix, $escape) . '%';
        }

        $query = sprintf('SELECT %s FROM information_schema.tables WHERE %s;', $column, $condition);
        $statement = $pdo->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $statement->execute($input);

        if (!$result) {
            // @codeCoverageIgnoreStart
            $this->throwRuntimeException(
                $statement,
                sprintf('Failed to obtain tables from database: %s', var_export($query, true)),
            );
        } // @codeCoverageIgnoreEnd

        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * throw a runtime exception and provide error info for the statement if available
     *
     * @throws RuntimeException
     */
    private function throwRuntimeException(PDOStatement $pdoStatement, string $message = ''): void
    {
        $reason = $pdoStatement->errorInfo()
            ? vsprintf('SQLSTATE[%s]: %s: %s', $pdoStatement->errorInfo())
            : 'no error info for statement';

        if (strlen($message) !== 0) {
            $message .= ': ';
        } else {
            $message = '';
        }

        throw new RuntimeException($message . $reason);
    }

    /**
     * quote a string so that it is safe to use in a LIKE
     *
     * @param string $escape character - single us-ascii character
     */
    private function quoteLike(string $string, string $escape = '='): string
    {
        $translation = [$escape => $escape . $escape, '%'     => $escape . '%', '_'     => $escape . '_'];
        return strtr($string, $translation);
    }

    /**
     * Get list of db tables status
     */
    public function getTablesStatus(bool $withoutPrefix = false): array
    {
        $pdo = $this->getConnection();
        $prefix = $this->dbSettings['prefix'];
        if ((string) $prefix !== '') {
            $statement = $pdo->prepare('SHOW TABLE STATUS LIKE :like', [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $statement->execute(
                [':like' => $prefix . '%'],
            );
        } else {
            $statement = $pdo->query('SHOW TABLE STATUS');
        }

        if ($statement) {
            $return = [];

            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (!$result) {
                return $return;
            }

            foreach ($result as $table) {
                if ($withoutPrefix) {
                    $table['Name'] = str_replace($prefix, '', $table['Name']);
                }

                $return[$table['Name']] = $table;
            }

            return $return;
        }

        return [];
    }

    public function getDbSettings(?OutputInterface $output = null): ?DbSettings
    {
        if ($this->dbSettings instanceof DbSettings) {
            return $this->dbSettings;
        }

        $output = $this->fallbackOutput($output);

        $this->detectDbSettings($output);

        if (!$this->dbSettings instanceof DbSettings) {
            throw new RuntimeException('Database settings fatal error');
        }

        return $this->dbSettings;
    }

    public function getIsSocketConnect(): bool
    {
        return $this->getDbSettings()->isSocketConnect();
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName(): string
    {
        return 'database';
    }

    public function dropDatabase(OutputInterface $output): void
    {
        $this->detectDbSettings($output);
        $pdo = $this->getConnection();
        $pdo->query('DROP DATABASE `' . $this->dbSettings['dbname'] . '`');

        $output->writeln('<info>Dropped database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

    public function dropTables(OutputInterface $output): void
    {
        $count = 0;
        $query = 'SET FOREIGN_KEY_CHECKS = 0; ';

        $result = $this->getTables();
        if ($result) {
            foreach ($result as $tableName) {
                $query .= 'DROP TABLE IF EXISTS `' . $tableName . '`; ';
                ++$count;
            }
        }

        $query .= 'SET FOREIGN_KEY_CHECKS = 1;';
        $this->getConnection()->query($query);

        $output->writeln('<info>Dropped database tables</info> <comment>' . $count . ' tables dropped</comment>');
    }

    public function createDatabase(OutputInterface $output): void
    {
        $this->detectDbSettings($output);
        $pdo = $this->getConnection();
        $pdo->query('CREATE DATABASE IF NOT EXISTS `' . $this->dbSettings['dbname'] . '`');

        $output->writeln('<info>Created database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

    /**
     * @param string $command example: 'VARIABLES', 'STATUS'
     * @param string|null $variable [optional]
     */
    private function runShowCommand(string $command, ?string $variable = null): array
    {
        $pdo = $this->getConnection();

        if (null !== $variable) {
            $statement = $pdo->prepare(
                'SHOW /*!50000 GLOBAL */ ' . $command . ' LIKE :like',
                [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
            );
            $statement->execute(
                [':like' => $variable],
            );
        } else {
            $statement = $pdo->query('SHOW /*!50000 GLOBAL */ ' . $command);
        }

        if ($statement) {
            /** @var array[] $result */
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            $return = [];
            foreach ($result as $row) {
                $return[$row['Variable_name']] = $row['Value'];
            }

            return $return;
        }

        return [];
    }

    /**
     * @param string|null $variable [optional]
     */
    public function getGlobalVariables(?string $variable = null): array
    {
        return $this->runShowCommand('VARIABLES', $variable);
    }

    /**
     * @param string|null $variable [optional]
     */
    public function getGlobalStatus(?string $variable = null): array
    {
        return $this->runShowCommand('STATUS', $variable);
    }

    /**
     * @return Application|BaseApplication
     */
    private function getApplication()
    {
        $command = $this->getHelperSet()->getCommand();

        if ($command) {
            return $command->getApplication();
        }

        return new Application();
    }

    /**
     * small helper method to obtain an object of type OutputInterface
     *
     *
     * @return OutputInterface
     */
    private function fallbackOutput(?OutputInterface $output = null)
    {
        if ($output instanceof \Symfony\Component\Console\Output\OutputInterface) {
            return $output;
        }

        if ($this->getHelperSet()->has('io')) {
            /** @var IoHelper $helper */
            $helper = $this->getHelperSet()->get('io');
            $output = $helper->getOutput();
        }

        if (null === $output) {
            return new NullOutput();
        }

        return $output;
    }
}
