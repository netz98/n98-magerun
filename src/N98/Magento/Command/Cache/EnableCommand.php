<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Attribute\AsCommand;

/**
     * Enable cache command
 *
 * @package N98\Magento\Command\Cache
 */
class EnableCommand extends AbstractCacheCommandToggle
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:enable';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
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
