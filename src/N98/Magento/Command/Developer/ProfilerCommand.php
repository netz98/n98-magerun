<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle profiler command
 *
 * @package N98\Magento\Command\Developer
 */
class ProfilerCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:profiler';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
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
