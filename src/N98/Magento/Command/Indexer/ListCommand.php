<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List index command
 *
 * @package N98\Magento\Command\Indexer
 */
class ListCommand extends AbstractIndexerCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'index:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all magento indexes.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Indexes';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['code', 'status', 'time'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $table = [];
        foreach ($this->getIndexerList() as $index) {
            $table[] = [
                $index['code'],
                $index['status'],
                $index['last_runtime']
            ];
        }

        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
Lists all Magento indexers of current installation.
HELP;
    }
}
