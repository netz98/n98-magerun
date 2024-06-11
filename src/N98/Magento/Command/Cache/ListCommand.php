<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCacheCommand implements AbstractMagentoCommandFormatInterface
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
     * @return array<int|string, array<string, string>>
     *
     *  phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
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
