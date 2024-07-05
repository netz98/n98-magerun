<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Compressor;

/**
 * Class Uncompressed
 *
 * @package N98\Magento\Command\Database\Compressor
 */
class Uncompressed extends AbstractCompressor
{
    /**
     * Returns the command line for compressing the dump file.
     *
     * @param string $command
     * @param bool $pipe
     * @return string
     */
    public function getCompressingCommand(string $command, bool $pipe = true): string
    {
        return $command;
    }

    /**
     * Returns the command line for decompressing the dump file.
     *
     * @param string $command MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     * @param bool $pipe
     * @return string
     */
    public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string
    {
        if ($this->hasPipeViewer()) {
            return 'pv ' . $fileName . ' | ' . $command;
        }

        return $command . ' < ' . $fileName;
    }

    /**
     * Returns the file name for the compressed dump file.
     *
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    public function getFileName(string $fileName, bool $pipe = true): string
    {
        if (!strlen($fileName)) {
            return $fileName;
        }

        if (substr($fileName, -4, 4) !== '.sql' && substr($fileName, -4, 4) !== '.xml') {
            $fileName .= '.sql';
        }

        return $fileName;
    }
}
