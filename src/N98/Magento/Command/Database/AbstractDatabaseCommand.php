<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Database\Compressor;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

abstract class AbstractDatabaseCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $dbSettings;

    /**
     * @var bool
     */
    protected $isSocketConnect = false;

    /**
     * @var \PDO
     */
    protected $_connection = null;

    /**
     * @param OutputInterface $output
     */
    protected function detectDbSettings(OutputInterface $output)
    {
        $this->detectMagento($output);
        $configFile = $this->_magentoRootFolder . '/app/etc/local.xml';

        $config = simplexml_load_file($configFile);
        if (!$config->global->resources->default_setup->connection) {
            $output->writeln('<error>DB settings was not found in local.xml file</error>');
            return;
        }
        $this->dbSettings = (array) $config->global->resources->default_setup->connection;
        if (isset($this->dbSettings['comment'])) {
            unset($this->dbSettings['comment']);
        }

        if (isset($this->dbSettings['unix_socket'])) {
            $this->isSocketConnect = true;
        }
    }

    /**
     * Generate help for compression
     *
     * @return string
     */
    protected function getCompressionHelp()
    {
        $messages = array();
        $messages[] = '';
        $messages[] = '<comment>Compression option</comment>';
        $messages[] = ' Supported compression: gzip';
        $messages[] = ' The gzip cli tool has to be installed.';
        $messages[] = ' Additionally, for data-to-csv option tar cli tool has to be installed too.';
        return implode(PHP_EOL, $messages);
    }

    /**
     * @param string $type
     * @return Compressor\AbstractCompressor
     * @throws \InvalidArgumentException
     */
    protected function getCompressor($type)
    {
        switch ($type) {
            case null:
                return new Compressor\Uncompressed;
            case 'gz':
            case 'gzip':
                return new Compressor\Gzip;
            default:
                throw new \InvalidArgumentException("Compression type '{$type}' is not supported.");
        }
    }

    /**
     * @return string
     */
    protected function getMysqlClientToolConnectionString()
    {
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
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @see Zend_Db_Adapter_Pdo_Abstract
     * @return string
     */
    protected function _dsn()
    {
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
     * Connects to the database without initializing magento
     *
     * @return \PDO
     * @throws \Exception
     */
    protected function _getConnection()
    {
        if ($this->_connection) {
            return $this->_connection;
        }

        if (!extension_loaded('pdo_mysql')) {
            throw new \Exception('pdo_mysql extension is not installed');
        }

        if (strpos($this->dbSettings['host'], '/') !== false) {
            $this->dbSettings['unix_socket'] = $this->dbSettings['host'];
            unset($this->dbSettings['host']);
        } else if (strpos($this->dbSettings['host'], ':') !== false) {
            list($this->dbSettings['host'], $this->dbSettings['port']) = explode(':', $this->dbSettings['host']);
        }

        $this->_connection = new \PDO(
            $this->_dsn(),
            $this->dbSettings['username'],
            $this->dbSettings['password']
        );

        /** @link http://bugs.mysql.com/bug.php?id=18551 */
        $this->_connection->query("SET SQL_MODE=''");

        try {
            $this->_connection->query('USE `'.$this->dbSettings['dbname'].'`');
        } catch(\PDOException $e) {
        }

        $this->_connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $this->_connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $this->_connection;
    }

    /**
     * @param array $excludes
     * @param array $definitions
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     * @return array
     * @throws \Exception
     */
    protected function resolveTables(array $excludes, array $definitions, array $resolved = array())
    {
        $resolvedExcludes = array();
        foreach ($excludes as $exclude) {
            if (substr($exclude, 0, 1) == '@') {
                $code = substr($exclude, 1);
                if (!isset($definitions[$code])) {
                    throw new \Exception('Table-groups could not be resolved: '.$exclude);
                }
                if (!isset($resolved[$code])) {
                    $resolved[$code] = true;
                    $tables = $this->resolveTables(explode(' ', $definitions[$code]['tables']), $definitions, $resolved);
                    $resolvedExcludes = array_merge($resolvedExcludes, $tables);
                }
                continue;
            }

            // resolve wildcards
            if (strpos($exclude, '*') !== false) {
                $connection = $this->_getConnection();
                $sth = $connection->prepare('SHOW TABLES LIKE :like', array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                $sth->execute(
                    array(':like' => str_replace('*', '%', $exclude))
                );
                $rows = $sth->fetchAll();
                foreach($rows as $row) {
                    $resolvedExcludes[] = $row[0];
                }
                continue;
            }

            $resolvedExcludes[] = $exclude;
        }

        asort($resolvedExcludes);
        $resolvedExcludes = array_unique($resolvedExcludes);

        return $resolvedExcludes;
    }


    /**
     * Get mysql tmpdir
     *
     * @return bool|string
     */
    protected function detectMysqlTmpDir()
    {
        $exec = 'mysql ' . $this->getMysqlClientToolConnectionString() . " -e 'SELECT @@tmpdir;'";

        exec($exec, $commandOutput, $returnValue);
        if (isset($commandOutput[1])) {
            return $commandOutput[1];
        }

        return false;
    }

    /**
     * Check whether current mysql user has $privilege privilege
     *
     * @param string $privilege
     * @return bool
     */
    protected function mysqlUserHasPrivilege($privilege)
    {
        $exec = 'mysql ' . $this->getMysqlClientToolConnectionString() . " -e 'SHOW GRANTS'";

        exec($exec, $commandOutput, $returnValue);
        if (isset($commandOutput[1])) {
            unset($commandOutput[0]);

            foreach ($commandOutput as $line) {
                if (preg_match('/^GRANT(.*)' . strtoupper($privilege) .'/', $line)
                    || preg_match('/^GRANT(.*)ALL/', $line)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get list of db tables
     *
     * @return bool|array
     */
    protected function getTables()
    {
        $exec = 'mysql ' . $this->getMysqlClientToolConnectionString() . " -e 'SHOW TABLES;'";

        exec($exec, $commandOutput, $returnValue);
        if (isset($commandOutput[1])) {
            unset($commandOutput[0]);
            return $commandOutput;
        }

        return false;
    }
}
