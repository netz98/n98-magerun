<?php

namespace N98\Magento\Command;

/**
 * Interface CommandConfigAware
 *
 * @package N98\Magento\Command
 */
interface CommandConfigAware
{
    /**
     * @param array $commandConfig
     * @return void
     */
    public function setCommandConfig(array $commandConfig);
}
