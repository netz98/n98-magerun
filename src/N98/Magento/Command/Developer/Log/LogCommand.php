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

    /**
     * @var string
     */
    protected $toggleComment = 'Development Log';

    /**
     * @var string
     */
    protected $configPath = 'dev/log/active';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
