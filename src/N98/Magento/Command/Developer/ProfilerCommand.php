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

    /**
     * @var string
     */
    protected $configPath = 'dev/debug/profiler';

    /**
     * @var string
     */
    protected $toggleComment = 'Profiler';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
