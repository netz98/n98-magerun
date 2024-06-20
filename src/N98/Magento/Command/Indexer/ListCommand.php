<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Exception;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List indexer command
 *
 * @package N98\Magento\Command\Indexer
 */
class ListCommand extends AbstractIndexerCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Indexes';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'index:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
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
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            foreach ($this->getIndexerList() as $index) {
                $this->data[] = [
                    'code'      => $index['code'],
                    'status'    => $index['status'],
                    'time'      => $index['last_runtime']
                ];
            }
        }

        return $this->data;
    }
}
