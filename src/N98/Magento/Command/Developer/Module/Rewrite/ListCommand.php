<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
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
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $rewrites = array_merge($this->loadRewrites(), $this->loadAutoloaderRewrites());

        $table = array();
        foreach ($rewrites as $type => $data) {
            if (count($data) > 0) {
                foreach ($data as $class => $rewriteClass) {
                    $table[] = array(
                        $type,
                        $class,
                        implode(', ', $rewriteClass),
                    );
                }
            }
        }

        if (count($table) === 0 && $input->getOption('format') === null) {
            $output->writeln('<info>No rewrites were found.</info>');
        } else {
            if (count($table) == 0) {
                $table = array();
            }
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('Type', 'Class', 'Rewrite'))
                ->setRows($table)
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }
}
