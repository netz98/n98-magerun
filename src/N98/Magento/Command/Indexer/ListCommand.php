<?php

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List index command
 *
 * @package N98\Magento\Command\Indexer
 */
class ListCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:list')
            ->setDescription('Lists all magento indexes')
            ->addFormatOption()
        ;
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $table = [];
        foreach ($this->getIndexerList() as $index) {
            $table[] = [$index['code'], $index['status'], $index['last_runtime']];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['code', 'status', 'time'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
