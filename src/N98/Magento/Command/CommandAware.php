<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Interface CommandAware
 *
 * @package N98\Magento\Command
 */
interface CommandAware
{
    /**
     * @param Command $command
     * @return void
     */
    public function setCommand(Command $command);
}
