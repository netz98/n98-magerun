<?php

declare(strict_types=1);

namespace N98\Magento\Command;

/**
 * Interface CommandConfigAware
 *
 * @package N98\Magento\Command
 */
interface CommandConfigAware
{
    public function setCommandConfig(array $commandConfig): void;
}
