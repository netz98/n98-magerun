<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Compressor;

use InvalidArgumentException;
use N98\Util\OperatingSystem;

/**
 * Class AbstractCompressor
 *
 * @package N98\Magento\Command\Database\Compressor
 */
abstract class AbstractCompressor implements Compressor
{
    /**
     * @return AbstractCompressor
     * @throws InvalidArgumentException
     */
    public static function create(?string $type)
    {
        switch ($type) {
            case null:
            case 'none':
                return new Uncompressed();

            case 'gz':
            case 'gzip':
                return new Gzip();

            default:
                throw new InvalidArgumentException(sprintf("Compression type '%s' is not supported.", $type));
        }
    }

    abstract public function getCompressingCommand(string $command, bool $pipe = true): string;

    abstract public function getDecompressingCommand(string $command, string $fileName, bool $pipe = true): string;

    abstract public function getFileName(string $fileName, bool $pipe = true): string;

    /**
     * Check whether pv is installed
     */
    protected function hasPipeViewer(): bool
    {
        return OperatingSystem::isProgramInstalled('pv');
    }
}
