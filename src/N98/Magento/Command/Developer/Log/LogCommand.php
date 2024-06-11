<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle development log command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class LogCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:log';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
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
