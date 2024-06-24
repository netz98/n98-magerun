<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Exception;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List indexer command
 *
 * @package N98\Magento\Command\Indexer
 */
class ListCommand extends AbstractIndexerCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Indexes';

    /**
     * @var string
     */
    protected static $defaultName = 'index:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all magento indexes.';

    public function getHelp(): string
    {
        return <<<HELP
Lists all Magento indexers of current installation.
HELP;
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     * @throws Exception
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        foreach ($this->getIndexerList() as $index) {
            $this->data[] = [
                'code'      => $index['code'],
                'status'    => $index['status'],
                'time'      => $index['last_runtime']
            ];
        }
    }
}
