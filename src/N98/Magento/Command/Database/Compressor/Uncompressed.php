<?php

namespace N98\Magento\Command\Database\Compressor;

class Uncompressed extends AbstractCompressor
{
    /**
     * Returns the command line for compressing the dump file.
     *
     * @param string $command
     * @param bool $pipe
     * @return string
     */
    public function getCompressingCommand($command, $pipe = true)
    {
        return $command;
    }

    /**
     * Returns the command line for decompressing the dump file.
     *
     * @param string $mysqlCmd MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     * @return string
     */
    public function getDecompressingCommand($mysqlCmd, $fileName)
    {
        if ($this->hasPipeViewer()) {
            return 'pv ' . $fileName . ' | ' . $mysqlCmd;
        }

        return $mysqlCmd . ' < ' . $fileName;
    }

    /**
     * Returns the file name for the compressed dump file.
     *
     * @param string $fileName
     * @param bool $pipe
     * @return string
     */
    public function getFileName($fileName, $pipe = true)
    {
        if (substr($fileName, -4, 4) !== '.sql') {
            $fileName .= '.sql';
        }

        return $fileName;
    }
}
