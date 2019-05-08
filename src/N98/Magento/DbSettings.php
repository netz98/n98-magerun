<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use InvalidArgumentException;
use IteratorAggregate;
use PDO;
use PDOException;
use RuntimeException;
use SimpleXMLElement;

/**
 * Class DbSettings
 *
 * Database settings.
 *
 * The Magento database settings are stored in a SimpleXMLElement structure
 *
 * @package N98\Magento
 */
class DbSettings implements ArrayAccess, IteratorAggregate
{
    /**
     * @var string|null known field members
     */
    private $tablePrefix, $host, $port, $unixSocket, $dbName, $username, $password;

    /**
     * @var array field array
     */
    private $config;

    /** @var string Connection Node from Local Xml */
    private $connectionNode = 'default_setup';

    /**
     * @param string $file path to app/etc/local.xml
     * @param null $connectionNode
     */
    public function __construct($file, $connectionNode = null)
    {
        $this->setFile($file);
        if (!is_null($connectionNode)) {
            $this->connectionNode = $connectionNode;
        }
    }

    /**
     * @param string $file path to app/etc/local.xml
     *
     * @throws InvalidArgumentException if the file is invalid
     */
    public function setFile($file)
    {
        if (!is_readable($file)) {
            throw new InvalidArgumentException(
                sprintf('"app/etc/local.xml"-file %s is not readable', var_export($file, true))
            );
        }

        $saved = libxml_use_internal_errors(true);
        $config = simplexml_load_file($file);
        libxml_use_internal_errors($saved);

        if (false === $config) {
            throw new InvalidArgumentException(
                sprintf('Unable to open "app/etc/local.xml"-file %s and parse it as XML', var_export($file, true))
            );
        }

        $resources = $config->global->resources;
        if (!$resources) {
            throw new InvalidArgumentException('DB global resources was not found in "app/etc/local.xml"-file');
        }

        $connectionNode = $this->connectionNode;
        if (!$resources->$connectionNode->connection) {
            throw new InvalidArgumentException(
                sprintf(
                    'DB settings (%s) was not found in "app/etc/local.xml"-file',
                    $connectionNode
                )
            );
        }

        $this->parseResources($resources);
    }

    /**
     * helper method to parse config file segment related to the database settings
     *
     * @param SimpleXMLElement $resources
     */
    private function parseResources(SimpleXMLElement $resources)
    {
        // default values
        $config = array(
            'host'        => null,
            'port'        => null,
            'unix_socket' => null,
            'dbname'      => null,
            'username'    => null,
            'password'    => null,
        );

        $connectionNode = $this->connectionNode;
        $config = array_merge($config, (array) $resources->$connectionNode->connection);
        $config['prefix'] = (string) $resources->db->table_prefix;

        // known parameters: host, port, unix_socket, dbname, username, password, options, charset, persistent,
        //                   driver_options
        //                   (port is deprecated; removed in magento 2, use port in host setting <host>:<port>)

        unset($config['comment']);

        /* @see Varien_Db_Adapter_Pdo_Mysql::_connect */
        if (strpos($config['host'], '/') !== false) {
            $config['unix_socket'] = (string) $config['host'];
            $config['host'] = null;
            $config['port'] = null;
        } elseif (strpos($config['host'], ':') !== false) {
            list($config['host'], $config['port']) = explode(':', $config['host']);
            $config['unix_socket'] = null;
        }

        $this->config = $config;

        $this->tablePrefix = $config['prefix'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->unixSocket = $config['unix_socket'];
        $this->dbName = $config['dbname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    /**
     * Get Mysql PDO DSN
     *
     * @return string
     */
    public function getDsn()
    {
        $dsn = 'mysql:';

        $named = array();

        // blacklisted in prev. DSN creation: username, password, options, charset, persistent, driver_options, dbname

        if (isset($this->unixSocket)) {
            $named['unix_socket'] = $this->unixSocket;
        } else {
            $named['host'] = $this->host;
            if (isset($this->port)) {
                $named['port'] = $this->port;
            }
        }

        $options = array();
        foreach ($named as $name => $value) {
            $options[$name] = "{$name}={$value}";
        }

        return $dsn . implode(';', $options);
    }

    /**
     * Connects to the database without initializing magento
     *
     * @throws RuntimeException if pdo_mysql extension is not installed
     * @return PDO
     */
    public function getConnection()
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension is not installed');
        }

        $database = $this->getDatabaseName();

        $connection = new PDO(
            $this->getDsn(),
            $this->getUsername(),
            $this->getPassword()
        );

        /** @link http://bugs.mysql.com/bug.php?id=18551 */
        $connection->query("SET SQL_MODE=''");

        try {
            $connection->query('USE ' . $this->quoteIdentifier($database));
        } catch (PDOException $e) {
            $message = sprintf("Unable to use database '%s': %s %s", $database, get_class($e), $e->getMessage());
            throw new RuntimeException($message, 0, $e);
        }

        $connection->query("SET NAMES utf8");

        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $connection;
    }

    public function getMysqlClientToolConnectionString()
    {
        $segments = array();

        if (null !== $this->config['unix_socket']) {
            $segments[] = '--socket=' . escapeshellarg($this->config['unix_socket']);
        } else {
            $segments[] = '-h ' . escapeshellarg($this->config['host']);
        }

        $segments[] = '-u' . escapeshellarg($this->config['username']);
        if (null !== $this->config['port']) {
            $segments[] = '-P' . escapeshellarg($this->config['port']);
        }
        if (strlen($this->config['password'])) {
            $segments[] = '-p' . escapeshellarg($this->config['password']);
        }
        $segments[] = escapeshellarg($this->config['dbname']);

        return implode(' ', $segments);
    }

    /**
     * Mysql quoting of an identifier
     *
     * @param string $identifier UTF-8 encoded
     *
     * @return string quoted identifier
     */
    private function quoteIdentifier($identifier)
    {
        $quote = '`'; // le backtique

        $pattern = '~^(?:[\x1-\x7F]|[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2})+$~';

        if (!preg_match($pattern, $identifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid identifier, must not contain NUL and must be UTF-8 encoded in the BMP: %s (hex: %s)',
                    var_export($identifier, true),
                    bin2hex($identifier)
                )
            );
        }

        return $quote . strtr($identifier, array($quote => $quote . $quote)) . $quote;
    }

    /**
     * @return bool
     */
    public function isSocketConnect()
    {
        return isset($this->config['unix_socket']);
    }

    /**
     * @return string table prefix, null if not in the settings (no or empty prefix)
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * @return string hostname, null if there is no hostname setup (e.g. unix_socket)
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string port, null if not setup
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string unix socket, null if not in use
     */
    public function getUnixSocket()
    {
        return $this->unixSocket;
    }

    /**
     * content of previous $dbSettings field of the DatabaseHelper
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string of the database identifier, null if not in use
     */
    public function getDatabaseName()
    {
        return $this->dbName;
    }

    /*
     * ArrayAccess interface
     */

    /**
     * @return boolean true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    /**
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (isset($this->config[$offset])) {
            return $this->config[$offset];
        }

        return;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('dbSettings are read-only');
    }

    /**
     * @param mixed $offset
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('dbSettings are read-only');
    }

    /*
     * IteratorAggregate
     */

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->config);
    }
}
