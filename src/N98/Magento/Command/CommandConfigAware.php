<?php

namespace N98\Magento\Command;

interface CommandConfigAware
{
    /**
     * @param array $commandConfig
     * @return void
     */
    public function setCommandConfig(array $commandConfig);
}
