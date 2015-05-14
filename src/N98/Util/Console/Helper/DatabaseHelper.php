<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use N98\Magento\Application;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var \PDO
     */
    protected $_connection = null;

    /**
     * @var array
     */
    protected $_tables;

    /**
     * @param OutputInterface $output
     * @param bool            $silent
     *
     * @throws \Exception
     *
     * @throws \Exception
     * @return void
     */
    public function detectDbSettings(OutputInterface $output, $silent = true)
    {
        if ($this->dbSettings == null) {
            $command = $this->getHelperSet()->getCommand();
            if ($command == null) {
                $application = new Application();
            } else {
                $application = $command->getApplication();
                /* @var $application Application */
            }
            $application->detectMagento();

            $configFile = $application->getMagentoRootFolder() . '/app/etc/local.xml';

            if (!is_readable($configFile)) {
                throw new \Exception('app/etc/local.xml is not readable');
            }
            $config = \simplexml_load_string(\file_get_contents($configFile));
            if (!$config->global->resources->default_setup->connection) {
                $output->writeln('<error>DB settings was not found in local.xml file</error>');
                return;
            }

            if (!isset($config->global->resources->default_setup->connection)) {
                throw new \Exception('Cannot find default_setup config in app/etc/local.xml');
            }

            $this->dbSettings           = (array)$config->global->resources->default_setup->connection;
            $this->dbSettings['prefix'] = (string)$config->global->resources->db->table_prefix;

            if (isset($this->dbSettings['host']) && strpos($this->dbSettings['host'], ':') !== false) {
                list($this->dbSettings['host'], $this->dbSettings['port']) = explode(':', $this->dbSettings['host']);
            }

            if (isset($this->dbSettings['comment'])) {
                unset($this->dbSettings['comment']);
            }

            if (isset($this->dbSettings['unix_socket'])) {
                $this->isSocketConnect = true;
            }

            // @see Varien_Db_Adapter_Pdo_Mysql->_connect()
            if (isset($this->dbSettings['host']) && strpos($this->dbSettings['host'], '/') !== false ) {
                $this->isSocketConnect = true;
                $this->dbSettings['unix_socket'] = $this->dbSettings['host'];
                unset($this->dbSettings['host']);
            }
        }
    }

    /**
     * Connects to the database without initializing magento
     *
     * @param OutputInterface $output = null
     *
     * @throws \Exception
     * @return \PDO
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
            throw new \Exception('pdo_mysql extension is not installed');
        }

        $this->_connection = new \PDO(
            $this->dsn(),
            $this->dbSettings['username'],
            $this->dbSettings['password']
        );

        /** @link http://bugs.mysql.com/bug.php?id=18551 */
        $this->_connection->query("SET SQL_MODE=''");

        try {
            $this->_connection->query('USE `' . $this->dbSettings['dbname'] . '`');
        } catch (\PDOException $e) {
        }

        $this->_connection->query("SET NAMES utf8");

        $this->_connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $this->_connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

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

        $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
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
            $string = '--socket=' . escapeshellarg(strval($this->dbSettings['unix_socket']));
        } else {
            $string = '-h' . escapeshellarg(strval($this->dbSettings['host']));
        }

        $string .= ' '
            . '-u' . escapeshellarg(strval($this->dbSettings['username']))
            . ' '
            . (isset($this->dbSettings['port']) ? '-P' . escapeshellarg($this->dbSettings['port']) . ' ' : '')
            . (!strval($this->dbSettings['password'] == '') ? '-p' . escapeshellarg($this->dbSettings['password']) . ' ' : '')
            . escapeshellarg(strval($this->dbSettings['dbname']));

        return $string;
    }

    /**
     * Get mysql variable value
     *
     * @param string $variable
     *
     * @return bool|string
     */
    public function getMysqlVariableValue($variable)
    {
        $statement = $this->getConnection()->query("SELECT @@{$variable};");
        $result    = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * @param $commandConfig
     *
     * @throws \Exception
     * @internal param $config
     * @return array $commandConfig
     * @return array
     */
    public function getTableDefinitions($commandConfig)
    {
        $tableDefinitions = array();
        if (isset($commandConfig['table-groups'])) {
            $tableGroups = $commandConfig['table-groups'];
            foreach ($tableGroups as $index => $definition) {
                $description = isset($definition['description']) ? $definition['description'] : '';
                if (!isset($definition['id'])) {
                    throw new \Exception('Invalid definition of table-groups (id missing) Index: ' . $index);
                }
                if (!isset($definition['id'])) {
                    throw new \Exception('Invalid definition of table-groups (tables missing) Id: '
                        . $definition['id']
                    );
                }

                $tableDefinitions[$definition['id']] = array(
                    'tables'      => $definition['tables'],
                    'description' => $description,
                );
            }
        };

        return $tableDefinitions;
    }

    /**
     * @param array $list
     * @param array $definitions
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     *
     * @return array
     * @throws \Exception
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
                    throw new \Exception('Table-groups could not be resolved: ' . $entry);
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
                $sth        = $connection->prepare('SHOW TABLES LIKE :like', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
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
     * Get list of db tables
     *
     * @param bool $withoutPrefix
     *
     * @return array
     */
    public function getTables($withoutPrefix = false)
    {
        $db     = $this->getConnection();
        $prefix = $this->dbSettings['prefix'];
        if (strlen($prefix) > 0) {
            $statement = $db->prepare('SHOW TABLES LIKE :like', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $statement->execute(
                array(':like' => $prefix . '%')
            );
        } else {
            $statement = $db->query('SHOW TABLES');
        }

        if ($statement) {
            $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
            if ($withoutPrefix === false) {
                return $result;
            }

            return array_map(function ($tableName) use ($prefix) {
                return str_replace($prefix, '', $tableName);
            }, $result);
        }

        return array();
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
            $statement = $db->prepare('SHOW TABLE STATUS LIKE :like', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $statement->execute(
                array(':like' => $prefix . '%')
            );
        } else {
            $statement = $db->query('SHOW TABLE STATUS');
        }

        if ($statement) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
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
     *
     * @throws \Exception
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
     *
     * @throws \Exception
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
     *
     * @throws \Exception
     */
    public function createDatabase($output)
    {
        $this->detectDbSettings($output);
        $db = $this->getConnection();
        $db->query('CREATE DATABASE IF NOT EXISTS `' . $this->dbSettings['dbname'] . '`');
        $output->writeln('<info>Created database</info> <comment>' . $this->dbSettings['dbname'] . '</comment>');
    }

    /**
     * @param string      $command
     * @param string|null $variable
     *
     * @return array
     * @throws \Exception
     */
    private function runShowCommand($command, $variable = null)
    {
        $db = $this->getConnection();

        if (null !== $variable) {
            $statement = $db->prepare(
                'SHOW /*!50000 GLOBAL */ ' . $command . ' LIKE :like',
                array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
            );
            $statement->execute(
                array(':like' => $variable)
            );
        } else {
            $statement = $db->query('SHOW /*!50000 GLOBAL */ ' . $command);
        }

        if ($statement) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $return = array();
            foreach ($result as $row) {
                $return[$row['Variable_name']] = $row['Value'];
            }
            return $return;
        }
        return array();
    }

    /**
     * @param string|null $variable
     *
     * @return array
     * @throws \Exception
     */
    public function getGlobalVariables($variable = null)
    {
        return $this->runShowCommand('VARIABLES', $variable);
    }

    /**
     * @param string|null $variable
     *
     * @return array
     * @throws \Exception
     */
    public function getGlobalStatus($variable = null)
    {
        return $this->runShowCommand('STATUS', $variable);
    }
}
