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
     * @param string $command
     * @param string $fileName Filename (shell argument escaped)
     * @param bool $pipe
     * @return string
     */
    public function getDecompressingCommand($command, $fileName, $pipe = true)
    {
        if ($pipe) {
            if ($this->hasPipeViewer()) {
                return 'pv -cN gzip ' . escapeshellarg($fileName) . ' | gzip -d | pv -cN mysql | ' . $command;
            }

            return 'gzip -dc < ' . escapeshellarg($fileName) . ' | ' . $command;
        } else {
            if ($this->hasPipeViewer()) {
                return 'pv -cN tar -zxf ' . escapeshellarg($fileName) . ' && pv -cN mysql | ' . $command;
            }

            return 'tar -zxf ' . escapeshellarg($fileName) . ' -C ' . dirname($fileName) . ' && ' . $command . ' < '
                . escapeshellarg(substr($fileName, 0, -4));
        }
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
        if (!strlen($fileName)) {
            return $fileName;
        }

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
