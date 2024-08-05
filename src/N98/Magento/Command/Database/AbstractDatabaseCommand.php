<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Database\Compressor\AbstractCompressor;
use N98\Magento\Command\Database\Compressor\Compressor;
use N98\Magento\DbSettings;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractDatabaseCommand
 *
 * @package N98\Magento\Command\Database
 */
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
     * @param null $connectionNode
     */
    protected function detectDbSettings(OutputInterface $output, $connectionNode = null)
    {
        $database = $database = $this->getDatabaseHelper();
        $this->dbSettings = $database->getDbSettings($output);
    }

    /**
     * @param $name
     *
     * @return \PDO|void
     */
    public function __get($name)
    {
        if ($name == '_connection') {
            // TODO(tk): deprecate
            return $this->getDatabaseHelper()->getConnection();
        }
    }

    /**
     * Generate help for compression
     *
     * @return string
     */
    protected function getCompressionHelp()
    {
        $messages = [];
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
     * @deprecated Since 1.97.29; use AbstractCompressor::create() instead
     */
    protected function getCompressor($type)
    {
        return AbstractCompressor::create($type);
    }

    /**
     * @return string
     *
     * @deprecated Please use database helper
     */
    protected function getMysqlClientToolConnectionString()
    {
        return $this->getDatabaseHelper()->getMysqlClientToolConnectionString();
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
        return $this->getDatabaseHelper()->dsn();
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
    protected function resolveTables(array $excludes, array $definitions, array $resolved = [])
    {
        return $this->getDatabaseHelper()->resolveTables($excludes, $definitions, $resolved);
    }
}
