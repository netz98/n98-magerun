<?php

namespace N98\Magento\Command\Database\Compressor;

use InvalidArgumentException;
use N98\Util\OperatingSystem;

abstract class AbstractCompressor implements Compressor
{
    /**
     * @param string $type
     * @return AbstractCompressor
     * @throws InvalidArgumentException
     */
    public static function create($type)
    {
        switch ($type) {
            case null:
            case 'none':
                return new Uncompressed;

            case 'gz':
            case 'gzip':
                return new Gzip;

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
    abstract public function getCompressingCommand($command, $pipe = true);

    /**
     * Returns the command line for decompressing the dump file.
     *
     * @param string $command MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     * @param bool $pipe
     * @return string
     */
    abstract public function getDecompressingCommand($command, $fileName, $pipe = true);

    /**
     * Returns the file name for the compressed dump file.
     *
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    abstract public function getFileName($fileName, $pipe = true);

    /**
     * Check whether pv is installed
     *
     * @return bool
     */
    protected function hasPipeViewer()
    {
        return OperatingSystem::isProgramInstalled('pv');
    }
}
