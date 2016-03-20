<?php

namespace N98\Magento\Command\Database;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Database\Compressor\Compressor;
use N98\Magento\Command\Database\Compressor\Gzip;
use N98\Magento\Command\Database\Compressor\Uncompressed;
use N98\Magento\DbSettings;
use N98\Util\Console\Helper\DatabaseHelper;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDatabaseCommand extends AbstractMagentoCommand
{
    /**
     * @var array|DbSettings
     */
    protected $dbSettings;

    /**
     * @var bool
     */
    protected $isSocketConnect = false;


    /**
     * @param OutputInterface $output
     */
    protected function detectDbSettings(OutputInterface $output)
    {
        /* @var $database DatabaseHelper */
        $database = $this->getHelper('database');
        $this->dbSettings = $database->getDbSettings($output);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == '_connection') {
            return $this->getHelper('database')->getConnection();
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
     * @return Compressor
     * @throws InvalidArgumentException
     */
    protected function getCompressor($type)
    {
        switch ($type) {
            case null:
                return new Uncompressed;

            case 'gz':
            case 'gzip':
                return new Gzip;

            default:
                throw new InvalidArgumentException("Compression type '{$type}' is not supported. Known values are: gz, gzip");
        }
    }

    /**
     * @return string
     *
     * @deprecated Please use database helper
     */
    protected function getMysqlClientToolConnectionString()
    {
        /** @see DatabaseHelper::getMysqlClientToolConnectionString */
        return $this->getHelper('database')->getMysqlClientToolConnectionString();
    }

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @see Zend_Db_Adapter_Pdo_Abstract
     * @return string
     *
     * @deprecated Please use database helper
     */
    protected function _dsn()
    {
        return $this->getHelper('database')->dsn();
    }

    /**
     * @param array $excludes
     * @param array $definitions
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     *
     * @return array
     *
     * @deprecated Please use database helper
     */
    protected function resolveTables(array $excludes, array $definitions, array $resolved = array())
    {
        return $this->getHelper('database')->resolveTables($excludes, $definitions, $resolved);
    }
}
