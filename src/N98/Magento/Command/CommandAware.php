<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;

interface CommandAware
{
    /**
     * @param Command $command
     */
    public function setCommand(Command $command);
}