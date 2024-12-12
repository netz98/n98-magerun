<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle profiler command
 *
 * @package N98\Magento\Command\Developer
 */
class ProfilerCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:profiler';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles profiler for debugging';

    protected string $configPath = 'dev/debug/profiler';

    protected string $toggleComment = 'Profiler';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
