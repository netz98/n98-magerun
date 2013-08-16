<?php

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:list')
            ->setDescription('Lists all magento indexes')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $table = array();
            foreach ($this->getIndexerList() as $index) {
                $table[] = array(
                    $index['code'],
                    $index['status'],
                    $index['last_runtime'],
                );
            }

            $this->getHelper('table')
                ->setHeaders(array('code', 'status', 'time'))
                ->setRows($table)
                ->render($output, $table);
        }
    }
}