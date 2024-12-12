<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle log command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class LogCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:log';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggle development log (system.log, exception.log)';

    protected string $toggleComment = 'Development Log';

    protected string $configPath = 'dev/log/active';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
