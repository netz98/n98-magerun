<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Database\Compressor\AbstractCompressor;
use N98\Magento\Command\Database\Compressor\Compressor;
use N98\Magento\DbSettings;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractDatabaseCommand
 *
 * @package N98\Magento\Command\Database
 */
abstract class AbstractDatabaseCommand extends AbstractMagentoCommand
{
    protected DbSettings $dbSettings;

    protected bool $isSocketConnect = false;

    protected function detectDbSettings(OutputInterface $output, ?string $connectionNode = null): void
    {
        $databaseHelper     = $this->getDatabaseHelper();
        $this->dbSettings   = $databaseHelper->getDbSettings($output);
    }

    /**
     * @return PDO|null
     */
    public function __get(string $name)
    {
        if ($name === '_connection') {
            // TODO(tk): deprecate
            return $this->getDatabaseHelper()->getConnection();
        }

        return null;
    }

    /**
     * Generate help for compression
     */
    protected function getCompressionHelp(): string
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
     * @deprecated Since 1.97.29; use AbstractCompressor::create() instead
     */
    protected function getCompressor(?string $type): Compressor
    {
        return AbstractCompressor::create($type);
    }

    /**
     * @deprecated Please use database helper
     */
    protected function getMysqlClientToolConnectionString(): string
    {
        return $this->getDatabaseHelper()->getMysqlClientToolConnectionString();
    }

    /**
     * Creates a PDO DSN for the adapter from $this->_config settings.
     *
     * @see Zend_Db_Adapter_Pdo_Abstract
     * @deprecated Please use database helper
     */
    protected function _dsn(): string
    {
        return $this->getDatabaseHelper()->dsn();
    }

    /**
     * @param array $resolved Which definitions where already resolved -> prevent endless loops
     *
     * @deprecated Please use database helper
     */
    protected function resolveTables(array $excludes, array $definitions, array $resolved = []): array
    {
        return $this->getDatabaseHelper()->resolveTables($excludes, $definitions, $resolved);
    }
}
