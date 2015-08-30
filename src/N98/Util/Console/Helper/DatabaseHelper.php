<?php

namespace N98\Util\Console\Helper;

use InvalidArgumentException;
use N98\Magento\DbSettings;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use N98\Magento\Application;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DatabaseHelper
 *
 * @package N98\Util\Console\Helper
 */
class DatabaseHelper extends AbstractHelper
{
    /**
     * @var array
     */
    protected $dbSettings = null;

    /**
     * @var bool
     */
    protected $isSocketConnect = false;

    /**
     * @var PDO
     */
    protected $_connection = null;

    /**
     * @var array
     */
    protected $_tables;

    /**
     * @param OutputInterface $output
     *
     * @throws RuntimeException
     */
    public function detectDbSettings(OutputInterface $output)
    {
        if (null !== $this->dbSettings) {

            return;
        }

        // NEW

        $application = $this->getApplication();
        $application->detectMagento();

        $configFile = $application->getMagentoRootFolder() . '/app/etc/local.xml';
        $configSettings = new DbSettings($configFile);
        $config = new DbConfig($configSettings);

        // NEW END

        $resources = $this->getMagentoGlobalResources($output);

        $isSocketConnect      = null;
        $dbSettings           = (array) $resources->default_setup->connection;
        $dbSettings['prefix'] = (string) $resources->db->table_prefix;

        if (isset($dbSettings['host']) && strpos($dbSettings['host'], ':') !== false) {
            list($dbSettings['host'], $dbSettings['port']) = explode(':', $dbSettings['host']);
        }

        unset($dbSettings['comment']);

        /* @see \Varien_Db_Adapter_Pdo_Mysql::_connect() */
        if (isset($dbSettings['host']) && strpos($dbSettings['host'], '/') !== false) {
            $dbSettings['unix_socket'] = $dbSettings['host'];
            unset($dbSettings['host']);
        }

        if (isset($dbSettings['unix_socket'])) {
            $isSocketConnect = true;
        }

        $this->isSocketConnect = $isSocketConnect;
        $this->dbSettings      = $dbSettings;
    }

    /**
     * @param $output
     *
     * @return SimpleXMLElement
     */
    private function getMagentoGlobalResources(OutputInterface $output)
    {
        $config = $this->loadMagentoConfigFile($output);

        $resources = $config->global->resources;
        if (!$resources) {
            $this->giveError($output, 'DB global resources was not found in app/etc/local.xml file');
        }

        if (!$resources->default_setup->connection) {
            $this->giveError($output, 'DB settings (default_setup) was not found in app/etc/local.xml file');
        }

        return $resources;
    }

    private function loadMagentoConfigFile(OutputInterface $output)
    {
        $application = $this->getApplication();
        $application->detectMagento();

        $configFile = $application->getMagentoRootFolder() . '/app/etc/local.xml';

        if (!is_readable($configFile)) {
            $this->giveError($output, 'app/etc/local.xml is not readable');
        }

        $config = simplexml_load_file($configFile);
        if (false === $config) {
            $this->giveError($output, 'Unable to open file app/etc/local.xml and parse it as XML');
        }

        return $config;
    }

    /**
     * @param OutputInterface $output
     * @param string          $message
     */
    private function giveError(OutputInterface $output, $message)
    {
        $output->writeln('<error>' . $message . '</error>');

        throw new RuntimeException($message);
    }

    /**
     * Connects to the database without initializing magento
     *
     * @param OutputInterface $output = null
     *
     * @return PDO
     * @throws RuntimeException pdo mysql extension is not installed
     */
    public function getConnection(OutputInterface $output = null)
    {
        if ($output == null) {
            $output = new NullOutput();
        }

        if ($this->_connection) {
            return $this->_connection;
        }

        $this->detectDbSettings($output);

        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension is not installed');
        }

        $this->_connection = new PDO(
            $this->dsn(),
            $this->dbSettings['username'],
            $this->dbSettings['password']
        );

        /** @link http://bugs.mysql.com/bug.php?id=18551 */
        $this->_connection->query("SET SQL_MODE=''");

        try {
            $this->_connection->query('USE `' . $this->dbSettings['dbname'] . '`');
        } catch (PDOException $e) {
            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln(sprintf(
                    '<error>Failed to use database <comment>%s</comment>: %s</error>',
                    var_export($this->dbSettings['dbname'], true), $e->getMessage()
                ));
            }
        }

        $this->_connection->query("SET NAMES utf8");

        $this->_connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->_connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $this->_connection;
    }

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @see Zend_Db_Adapter_Pdo_Abstract
     * @return string
     */
    public function dsn()
    {
        $this->detectDbSettings(new NullOutput());

        // baseline of DSN parts
        $dsn = $this->dbSettings;

        // don't pass the username, password, charset, database, persistent and driver_options in the DSN
        unset($dsn['username']);
        unset($dsn['password']);
        unset($dsn['options']);
        unset($dsn['charset']);
        unset($dsn['persistent']);
        unset($dsn['driver_options']);
        unset($dsn['dbname']);

        // use all remaining parts in the DSN
        $buildDsn = array();
        foreach ($dsn as $key => $val) {
            if (is_array($val)) {
                continue;
            }
            $buildDsn[$key] = "$key=$val";
        }

        return 'mysql:' . implode(';', $buildDsn);
    }

    /**
     * Check whether current mysql user has $privilege privilege
     *
     * @param string $privilege
     *
     * @return bool
     */
    public function mysqlUserHasPrivilege($privilege)
    {
        $statement = $this->getConnection()->query('SHOW GRANTS');

        $result = $statement->fetchAll(PDO::FETCH_COLUMN);
        foreach ($result as $row) {
            if (preg_match('/^GRANT(.*)' . strtoupper($privilege) . '/', $row)
                || preg_match('/^GRANT(.*)ALL/', $row)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getMysqlClientToolConnectionString()
    {
        $this->detectDbSettings(new NullOutput());

        if ($this->isSocketConnect) {
            $string = '--socket=' . escapeshellarg($this->dbSettings['unix_socket']);
        } else {
            $string = '-h' . escapeshellarg($this->dbSettings['host']);
        }

        $string .= ' '
            . '-u' . escapeshellarg($this->dbSettings['username'])
            . ' '
            . (isset($this->dbSettings['port']) ? '-P' . escapeshellarg($this->dbSettings['port']) . ' ' : '')
            . (strlen($this->dbSettings['password']) ? '--pass=' . escapeshellarg($this->dbSettings['password']) . ' ' : '')
            . escapeshellarg($this->dbSettings['dbname']);

        return $string;
    }

    /**
     * Get mysql variable value
     *
     * @param string $variable
     *
     * @return bool|array returns array on success, false on failure
     */
    public function getMysqlVariableValue($variable)
    {
        $statement = $this->getConnection()->query("SELECT @@{$variable};");
        if (false === $statement) {
            throw new RuntimeException(sprintf('Failed to query mysql variable %s', var_export($variable, 1)));
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
     * in difference to @see getMysqlVariableValue(), this method allows to specify the type of the variable as well
     * as to use any variable identifier even such that need quoting.
     *
     * @param string $name mysql variable name
     * @param string $type [optional] variable type, can be a system variable ("@@", default) or a session variable
     *                     ("@").
     *
     * @return string variable value, null if variable was not defined
     * @throws RuntimeException in case a system variable is unknown (SQLSTATE[HY000]: 1193: Unknown system variable
     *                          'nonexistent')
     */
    public function getMysqlVariable($name, $type = null)
    {
        if (null === $type) {
            $type = "@@";
        } else {
            $type = (string) $type;
        }

        if (!in_array($type, array("@@", "@"), true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid mysql variable type "%s", must be "@@" (system) or "@" (session)', $type)
            );
        }

        $quoted = '`' . strtr($name, array('`' => '``')) . '`';
        $query  = "SELECT {$type}{$quoted};";

        $connection = $this->getConnection();
        $statement  = $connection->query($query, PDO::FETCH_COLUMN, 0);
        if ($statement instanceof PDOStatement) {
            $result = $statement->fetchColumn(0);
        } else {
            $reason = $connection->errorInfo()
                ? vsprintf('SQLSTATE[%s]: %s: %s', $connection->errorInfo())
                : 'no error info';

            throw new RuntimeException(
                sprintf('Failed to query mysql variable %s: %s', var_export($name, true), $reason)
            );
        }

        return $result;
    }


    /**
     * @param array $commandConfig
     *
     * @return array
     */
    public function getTableDefinitions(array $commandConfig)
    {
        $tableDefinitions = array();
        if (isset($commandConfig['table-groups'])) {
            $tableGroups = $commandConfig['table-groups'];
            foreach ($tableGroups as $index => $definition) {
                $description = isset($definition['description']) ? $definition['description'] : '';
                if (!isset($definition['id'])) {
                    throw new RuntimeException('Invalid definition of table-groups (id missing) Index: ' . $index);
                }
                if (!isset($definition['tables'])) {
                    throw new RuntimeException('Invalid definition of table-groups (tables missing) Id: '
                        . $definition['id']
                    );
                }

                $tableDefinitions[$definition['id']] = array(
                    'tables'      => $definition['tables'],
                    'description' => $description,
                );
            }
        }

        return $tableDefinitions;
    }

    /**
     * @param array $list
     * @param array $definitions
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     *
     * @return array
     * @throws RuntimeException
     */
    public function resolveTables(array $list, array $definitions = array(), array $resolved = array())
    {
        if ($this->_tables === null) {
            $this->_tables = $this->getTables(true);
        }

        $resolvedList = array();
        foreach ($list as $entry) {
            if (substr($entry, 0, 1) == '@') {
                $code = substr($entry, 1);
                if (!isset($definitions[$code])) {
                    throw new RuntimeException('Table-groups could not be resolved: ' . $entry);
                }
                if (!isset($resolved[$code])) {
                    $resolved[$code] = true;
                    $tables          = $this->resolveTables(explode(' ', $definitions[$code]['tables']), $definitions, $resolved);
                    $resolvedList    = array_merge($resolvedList, $tables);
                }
                continue;
            }

            // resolve wildcards
            if (strpos($entry, '*') !== false) {
                $connection = $this->getConnection();
                $sth        = $connection->prepare('SHOW TABLES LIKE :like', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
                $sth->execute(
                    array(':like' => str_replace('*', '%', $this->dbSettings['prefix'] . $entry))
                );
                $rows = $sth->fetchAll();
                foreach ($rows as $row) {
                    $resolvedList[] = $row[0];
                }
                continue;
            }

            if (in_array($entry, $this->_tables)) {
                $resolvedList[] = $this->dbSettings['prefix'] . $entry;
            }
        }

        asort($resolvedList);
        $resolvedList = array_unique($resolvedList);

        return $resolvedList;
    }

    /**
     * Get list of database tables
     *
     * @param bool $withoutPrefix [optional] remove prefix from the returned table names. prefix is obtained from
     *                            magento database configuration. defaults to false.
     *
     * @return array
     * @throws RuntimeException
     */
    public function getTables($withoutPrefix = null)
    {
        $withoutPrefix = (bool) $withoutPrefix;

        $db     = $this->getConnection();
        $prefix = $this->dbSettings['prefix'];
        $length = strlen($prefix);

        $columnName = 'table_name';
        $column     = $columnName;

        $input = array();

        if ($withoutPrefix && $length) {
            $column         = sprintf('SUBSTRING(%1$s FROM 1 + CHAR_LENGTH(:name)) %1$s', $columnName);
            $input[':name'] = $prefix;
        }

        $condition = 'table_schema = database()';

        if ($length) {
            $escape = '=';
            $condition .= sprintf(" AND %s LIKE :like ESCAPE '%s'", $columnName, $escape);
            $input[':like'] = $this->quoteLike($prefix, $escape) . '%';
        }

        $query     = sprintf('SELECT %s FROM information_schema.tables WHERE %s;', $column, $condition);
        $statement = $db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $result    = $statement->execute($input);

        if (!$result) {
            // @codeCoverageIgnoreStart
            $this->throwRuntimeException(
                $statement
                , sprintf('Failed to obtain tables from database: %s', var_export($query, true))
            );
        } // @codeCoverageIgnoreEnd

        $result = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        return $result;
    }

    /**
     * throw a runtime exception and provide error info for the statement if available
     *
     * @param PDOStatement $statement
     * @param string       $message
     *
     * @throws RuntimeException
     */
    private function throwRuntimeException(PDOStatement $statement, $message = "")
    {
        $reason = $statement->errorInfo()
            ? vsprintf('SQLSTATE[%s]: %s: %s', $statement->errorInfo())
            : 'no error info for statement';

        if (strlen($message)) {
            $message .= ': ';
        } else {
            $message = '';
        }

        throw new RuntimeException($message . $reason);
    }

    /**
     * quote a string so that it is safe to use in a LIKE
     *
     * @param string $string
     * @param string $escape character - single us-ascii character
     *
     * @return string
     */
    private function quoteLike($string, $escape = '=')
    {
        $translation = array(
            $escape => $escape . $escape,
            '%'     => $escape . '%',
            '_'     => $escape . '_',
        );

        return strtr($string, $translation);
    }

    /**
     * Get list of db tables status
     *
     * @param bool $withoutPrefix
     *
     * @return array
     */
    public function getTablesStatus($withoutPrefix = false)
    {
        $db     = $this->getConnection();
        $prefix = $this->dbSettings['prefix'];
        if (strlen($prefix) > 0) {
            $statement = $db->prepare('SHOW TABLE STATUS LIKE :like', array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $statement->execute(
                array(':like' => $prefix . '%')
            );
        } else {
            $statement = $db->query('SHOW TABLE STATUS');
        }

        if ($statement) {
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            $return = array();
            foreach ($result as $table) {
                if (true === $withoutPrefix) {
                    $table['Name'] = str_replace($prefix, '', $table['Name']);
                }
                $return[$table['Name']] = $table;
            }

            return $return;
        }

        return array();
    }

    /**
     * @return array
     */
    public function getDbSettings()
    {
        return $this->dbSettings;
    }

    /**
     * @return boolean
     */
    public function getIsSocketConnect()
    {
        return $this->isSocketConnect;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'database';
    }

    /**
     * @param OutputInterface $output
     */
    public function dropDatabase($output)
    {
        $this->detectDbSettings($output);
        $db = $this->getConnection();
        $db->query('DROP DATABASE `' . $this->dbSettings['dbname'] . '`');
        $output->writeln('<info>Dropped database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    public function dropTables($output)
    {
        $result = $this->getTables();
        $query  = 'SET FOREIGN_KEY_CHECKS = 0; ';
        $count  = 0;
        foreach ($result as $tableName) {
            $query .= 'DROP TABLE IF EXISTS `' . $tableName . '`; ';
            $count++;
        }
        $query .= 'SET FOREIGN_KEY_CHECKS = 1;';
        $this->getConnection()->query($query);
        $output->writeln('<info>Dropped database tables</info> <comment>' . $count . ' tables dropped</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    public function createDatabase($output)
    {
        $this->detectDbSettings($output);
        $db = $this->getConnection();
        $db->query('CREATE DATABASE IF NOT EXISTS `' . $this->dbSettings['dbname'] . '`');
        $output->writeln('<info>Created database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

    /**
     * @param string $command  example: 'VARIABLES', 'STATUS'
     * @param string $variable [optional]
     *
     * @return array
     */
    private function runShowCommand($command, $variable = null)
    {
        $db = $this->getConnection();

        if (null !== $variable) {
            $statement = $db->prepare(
                'SHOW /*!50000 GLOBAL */ ' . $command . ' LIKE :like',
                array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
            );
            $statement->execute(
                array(':like' => $variable)
            );
        } else {
            $statement = $db->query('SHOW /*!50000 GLOBAL */ ' . $command);
        }

        if ($statement) {
            /** @var array|string[] $result */
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            $return = array();
            foreach ($result as $row) {
                $return[$row['Variable_name']] = $row['Value'];
            }

            return $return;
        }

        return array();
    }

    /**
     * @param string $variable [optional]
     *
     * @return array
     */
    public function getGlobalVariables($variable = null)
    {
        return $this->runShowCommand('VARIABLES', $variable);
    }

    /**
     * @param string $variable [optional]
     *
     * @return array
     */
    public function getGlobalStatus($variable = null)
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
            $application = $command->getApplication();
        } else {
            $application = new Application();
        }

        return $application;
    }
}
