<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Caches';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['code', 'status'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
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
