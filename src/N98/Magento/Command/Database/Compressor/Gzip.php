<?php

namespace N98\Magento\Command\Database\Compressor;

class Gzip extends AbstractCompressor
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
        if ($pipe) {
            return $command . ' | gzip -c ';
        } else {
            return  'tar -czf ' . $command;
        }
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
            return 'pv -cN gzip ' . $fileName . ' | gzip -d | pv -cN mysql | ' . $mysqlCmd;
        }

        return 'gzip -dc < ' . $fileName . ' | ' . $mysqlCmd;
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
        if ($pipe) {
            if (substr($fileName, -3, 3) === '.gz') {
                return $fileName;
            } elseif (substr($fileName, -4, 4) === '.sql') {
                $fileName .= '.gz';
            } else {
                $fileName .= '.sql.gz';
            }
        } else {
            if (substr($fileName, -4, 4) === '.tgz') {
                return $fileName;
            } else {
                $fileName .= '.tgz';
            }
        }

        return $fileName;
    }
}
