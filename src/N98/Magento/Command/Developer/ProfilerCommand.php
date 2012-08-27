<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class ProfilerCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:profiler';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles profiler for debugging';

    /**
     * @var string
     */
    protected $configPath = 'dev/debug/profiler';

    /**
     * @var string
     */
    protected $toggleComment = 'Profiler';
}