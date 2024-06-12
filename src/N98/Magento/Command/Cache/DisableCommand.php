<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Disable cache command
 *
 * @package N98\Magento\Command\Cache
 */
class DisableCommand extends AbstractCacheCommandToggle
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:disable';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Disables caches.';

    /**
     * @var bool
     */
    protected static bool $cacheStatus = false;

    /**
     * @var string
     */
    protected static string $toggleName = 'disabled';
}
