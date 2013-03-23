<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\AbstractMagentoCommand;
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
     * @param \Symfony\Component\Console\Output\OutputInterface $output
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

        // don't pass the username, password, charset, persistent and driver_options in the DSN
        unset($dsn['username']);
        unset($dsn['password']);
        unset($dsn['options']);
        unset($dsn['charset']);
        unset($dsn['persistent']);
        unset($dsn['driver_options']);

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
            $this->dbSettings['unix_socket'] = $this->_config['host'];
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
        $this->_connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $this->_connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $this->_connection;
    }
}