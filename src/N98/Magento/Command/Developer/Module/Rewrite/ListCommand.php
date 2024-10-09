<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_merge;
use function count;
use function implode;
use function is_array;
use function is_countable;

/**
 * List module rewrites command
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ListCommand extends AbstractRewriteCommand implements CommandFormatable
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:rewrite:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all rewrites.';

    /**
     * @var string
     */
    protected static string $noResultMessage = 'No rewrites were found.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Module rewrites';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['Type', 'Class', 'Rewrite'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        $rewrites = array_merge($this->loadRewrites(), $this->loadAutoloaderRewrites());

        $this->data = [];
        foreach ($rewrites as $type => $data) {
            if ((is_countable($data) ? count($data) : 0) > 0) {
                foreach ($data as $class => $rewriteClass) {
                    $this->data[] = [
                        $type,
                        $class,
                        implode(', ', $rewriteClass)
                    ];
                }
            }
        }

        return $this->data;
    }
}
