<?php

namespace N98\Magento\Command\Database\Compressor;

use N98\Util\OperatingSystem;

abstract class AbstractCompressor
{
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
     * @param string $mysqlCmd MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     * @return string
     */
    abstract public function getDecompressingCommand($mysqlCmd, $fileName);

    /**
     * Returns the file name for the compressed dump file.
     *
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    abstract public function getFileName($fileName, $pipe = true);

    /**
     * @return bool
     */
    protected function hasPipeViewer()
    {
        if (OperatingSystem::isWindows()) {
            return false;
        }

        return `which pv` != '';
    }
}
