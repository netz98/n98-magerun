<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Compressor;

/**
 * Interface Compressor
 *
 * @package N98\Magento\Command\Database\Compressor
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
interface Compressor
{
    /**
     * Returns the command line for compressing the dump file.
     */
    public function getCompressingCommand(string $command, bool $pipe = true): string;

    /**
     * Returns the command line for decompressing the dump file.
     *
     * @param string $command MySQL client tool connection string
     * @param string $fileName Filename (shell argument escaped)
     */
    public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string;

    /**
     * Returns the file name for the compressed dump file.
     */
    public function getFileName(string $fileName, bool $pipe = true): string;
}
