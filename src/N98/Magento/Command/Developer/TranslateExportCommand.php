<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TranslateExportCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:translate:export')
            ->setDescription('Export inline translations')
            ->addArgument('locale', InputOption::VALUE_REQUIRED, 'Locale')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Export filename')
            ->addOption('store-view', 's', InputOption::VALUE_REQUIRED, 'Store View')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getHelper('database')->getConnection();

        $filename = $input->getArgument('filename');

        if(!$filename) {
            $filename = 'translate.csv';
        }

        $locale = $input->getArgument('locale');
        $where = 'WHERE locale = "' .  $locale . '"';
        $output->writeln('Exporting to <info>' . $filename . '</info>');

        $result = $db->query("SELECT * FROM core_translate $where");

        $f = fopen($filename, 'w');

        foreach($result as $row) {
            var_dump($row);
            fputcsv($f,array($row['string'],$row['translate']));
        }

        fclose($f);
    }
}