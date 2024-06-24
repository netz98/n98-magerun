<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

/**
 * Disable cache command
 *
 * @package N98\Magento\Command\Cache
 */
class DisableCommand extends AbstractCacheCommandToggle
{
    /**
     * @var string
     */
    protected static $defaultName = 'cache:disable';

    /**
     * @var string
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
