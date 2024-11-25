<?php

declare(strict_types=1);

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
     * @return void
     */
    public function setCommand(Command $command);
}
