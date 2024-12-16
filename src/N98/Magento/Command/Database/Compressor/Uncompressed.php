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
    public function getCompressingCommand(string $command, bool $pipe = true): string
    {
        return $command;
    }

    public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string
    {
        if ($this->hasPipeViewer()) {
            return 'pv ' . $fileName . ' | ' . $command;
        }

        return $command . ' < ' . $fileName;
    }

    public function getFileName(string $fileName, bool $pipe = true): string
    {
        if ($fileName === '') {
            return $fileName;
        }

        if (substr($fileName, -4, 4) !== '.sql' && substr($fileName, -4, 4) !== '.xml') {
            $fileName .= '.sql';
        }

        return $fileName;
    }
}
