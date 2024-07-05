<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Compressor;

use InvalidArgumentException;
use N98\Util\OperatingSystem;

/**
 * Class AbstractCompressor
 *
 * @package N98\Magento\Command\Database\Compressor
 */
abstract class AbstractCompressor implements Compressor
{
    /**
     * @param string|null $type
     * @return AbstractCompressor
     */
    public static function create(?string $type)
    {
        switch ($type) {
            case null:
            case 'none':
                return new Uncompressed();

            case 'gz':
            case 'gzip':
                return new Gzip();

            default:
                throw new InvalidArgumentException("Compression type '{$type}' is not supported.");
        }
    }

    /**
     * Returns the command line for compressing the dump file.
     *
     * @param string $command
     * @param bool $pipe
     * @return string
     */
    abstract public function getCompressingCommand(string $command, bool $pipe = true): string;

    /**
     * Returns the command line for decompressing the dump file.
     *
     * @param string $command MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     * @param bool $pipe
     * @return string
     */
    abstract public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string;

    /**
     * Returns the file name for the compressed dump file.
     *
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    abstract public function getFileName(string $fileName, bool $pipe = true): string;

    /**
     * Check whether pv is installed
     *
     * @return bool
     */
    protected function hasPipeViewer(): bool
    {
        return OperatingSystem::isProgramInstalled('pv');
    }
}
