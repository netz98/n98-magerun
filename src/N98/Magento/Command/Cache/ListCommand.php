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
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Code', 'Status'];
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];
            foreach ($this->getAllCacheTypes() as $cacheCode => $cacheInfo) {
                $this->data[] = [
                    $cacheCode,
                    $cacheInfo['status'] ? 'enabled' : 'disabled'
                ];
            }
        }

        return $this->data;
    }
}
