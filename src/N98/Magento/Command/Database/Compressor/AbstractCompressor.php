<?php

namespace N98\Magento\Command\Database\Compressor;

use N98\Util\OperatingSystem;

abstract class AbstractCompressor implements Compressor
{
    /**
     * @inheritdoc
     */
    abstract public function getCompressingCommand($command, $pipe = true);

    /**
     * @inheritdoc
     */
    abstract public function getDecompressingCommand($command, $fileName, $pipe = true);

    /**
     * @inheritdoc
     */
    abstract public function getFileName($fileName, $pipe = true);

    /**
     * Check whether pv is installed
     *
     * @return bool
     */
    protected function hasPipeViewer()
    {
        return OperatingSystem::isProgramInstalled('pv');
    }
}
