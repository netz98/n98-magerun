<?php

namespace N98\Magento\Command;

interface CommandConfigAware
{
    /**
     * @param array $commandConfig
     */
    public function setCommandConfig(array $commandConfig);
}