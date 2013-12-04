<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractRewriteCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:rewrite:list')
            ->setDescription('Lists all magento rewrites')
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

            $rewrites = $this->loadRewrites();
            $table = array();
            foreach ($rewrites as $type => $data) {
                if (count($data) > 0) {
                    foreach ($data as $class => $rewriteClass) {
                        $table[] = array(
                            $type,
                            $class,
                            implode(', ', $rewriteClass)
                        );
                    }
                }
            }

            if (count($table) > 0) {
                $this->getHelper('table')
                    ->setHeaders(array('Type', 'Class', 'Rewrite'))
                    ->setRows($table)
                    ->render($output);
            } else {
                $output->writeln('<info>No rewrites were found.</info>');
            }
        }
    }
}
