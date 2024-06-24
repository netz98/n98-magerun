<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\AbstractStoreConfigCommand;

/**
 * Toggle development log command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class LogCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:log';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggle development log (system.log, exception.log)';

    /**
     * @var string
     */
    protected string $toggleComment = 'Development Log';

    /**
     * @var string
     */
    protected string $configPath = 'dev/log/active';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
