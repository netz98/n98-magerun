<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\CommandFormatable;

/**
 * List cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ListCommand extends AbstractCacheCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'cache:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all magento caches.';

    /**
     * @return string
     */
    public function getSectionTitle(): string
    {
        return 'Caches';
    }

    /**
     * @return string[]
     */
    public function getListHeader(): array
    {
        return ['code', 'status'];
    }

    /**
     * @return array
     */
    public function getListData(): array
    {
        $table = [];
        $cacheTypes = $this->_getCacheModel()->getTypes();
        foreach ($cacheTypes as $cacheCode => $cacheInfo) {
            $table[] = [
                $cacheCode,
                $cacheInfo['status'] ? 'enabled' : 'disabled'
            ];
        }

        return $table;
    }
}
