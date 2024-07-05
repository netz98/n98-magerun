<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache\Toggle;

/**
 * Enable cache command
 *
 * @package N98\Magento\Command\Cache\Toggle
 */
class EnableCommand extends AbstractCacheCommandToggle
{
    /**
     * @var string
     */
    protected static $defaultName = 'cache:enable';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Enables caches.';

    /**
     * @var bool
     */
    protected static bool $cacheStatus = true;


    /**
     * @var string
     */
    protected static string $toggleName = 'enabled';
}
