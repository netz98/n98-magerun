<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Compressor;

/**
 * Class Gzip
 *
 * @package N98\Magento\Command\Database\Compressor
 */
class Gzip extends AbstractCompressor
{
    public function getCompressingCommand(string $command, bool $pipe = true): string
    {
        if ($pipe) {
            return $command . ' | gzip -c ';
        }

        return  'tar -czf ' . $command;
    }

    public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string
    {
        if ($pipe) {
            if ($this->hasPipeViewer()) {
                return 'pv -cN gzip ' . escapeshellarg($fileName) . ' | gzip -d | pv -cN mysql | ' . $command;
            }

            return 'gzip -dc < ' . escapeshellarg($fileName) . ' | ' . $command;
        }

        if ($this->hasPipeViewer()) {
            return 'pv -cN tar -zxf ' . escapeshellarg($fileName) . ' && pv -cN mysql | ' . $command;
        }

        return 'tar -zxf ' . escapeshellarg($fileName) . ' -C ' . dirname($fileName) . ' && ' . $command . ' < '
            . escapeshellarg(substr($fileName, 0, -4));
    }

    public function getFileName(string $fileName, bool $pipe = true): string
    {
        if ($fileName === '') {
            return $fileName;
        }

        if ($pipe) {
            if (substr($fileName, -3, 3) === '.gz') {
                return $fileName;
            }

            if (substr($fileName, -4, 4) === '.sql') {
                $fileName .= '.gz';
            } else {
                $fileName .= '.sql.gz';
            }
        } elseif (substr($fileName, -4, 4) === '.tgz') {
            return $fileName;
        } else {
            $fileName .= '.tgz';
        }

        return $fileName;
    }
}
