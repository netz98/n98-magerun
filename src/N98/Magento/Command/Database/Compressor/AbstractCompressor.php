<?php

namespace N98\Magento\Command\Database\Compressor;

abstract class AbstractCompressor
{
    /**
     * Returns the command line for compressing the dump file.
     * 
     * @param string $command
     * @return string
     */
    abstract public function getCompressingCommand($command);
    
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
     * @return string
     */
    abstract public function getFileName($fileName);

    /**
     * @return bool
     */
    protected function hasPipeViewer()
    {
        return `which pv` !== '';
    }
}