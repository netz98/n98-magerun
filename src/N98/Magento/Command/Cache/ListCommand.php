<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ListCommand extends AbstractCacheCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Caches';

    /**
     * @var string
     */
    protected static $defaultName = 'cache:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all caches.';

    /**
     * {@inheritdoc}
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        foreach ($this->_getCacheModel()->getTypes() as $cacheCode => $cacheInfo) {
            $this->data[] = [
                'code'      => $cacheCode,
                'status'    => $cacheInfo['status'] ? 'enabled' : 'disabled'
            ];
        }
    }
}
