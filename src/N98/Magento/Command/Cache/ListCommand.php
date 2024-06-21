<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ListCommand extends AbstractCacheCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Caches';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all caches.';

    /**
     * {@inheritdoc}
     * @return array<int<0, max>|string, array<string, int|string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];
            foreach ($this->_getCacheModel()->getTypes() as $cacheCode => $cacheInfo) {
                $this->data[] = [
                    'code'      => $cacheCode,
                    'status'    => $cacheInfo['status'] ? 'enabled' : 'disabled'
                ];
            }
        }

        return $this->data;
    }
}
