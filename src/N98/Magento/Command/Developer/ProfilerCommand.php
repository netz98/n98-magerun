<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractStoreConfigCommand;

/**
 * Toggle profiler command
 *
 * @package N98\Magento\Command\Developer
 */
class ProfilerCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:profiler';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles profiler for debugging';

    /**
     * @var string
     */
    protected string $configPath = 'dev/debug/profiler';

    /**
     * @var string
     */
    protected string $toggleComment = 'Profiler';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
