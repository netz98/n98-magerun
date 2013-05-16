<?php

namespace N98\Magento\Command\Database\Compressor;

class Gzip extends AbstractCompressor
{
    /**
     * Returns the command line for compressing the dump file.
     * 
     * @param string $command
     * @return string
     */
    public function getCompressingCommand($command)
    {
        return $command . ' | gzip -c ';
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
     * @return string
     */
    public function getFileName($fileName)
    {
        if (substr($fileName, -3, 3) === '.gz') {
            return $fileName;
        } elseif (substr($fileName, -4, 4) === '.sql') {
            $fileName .= '.gz';
        } else {
            $fileName .= '.sql.gz';
        }
        
        return $fileName;
    }
}
