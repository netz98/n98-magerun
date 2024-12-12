<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export translation command
 *
 * @package N98\Magento\Command\Developer\Translate
 */
class ExportCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:translate:export')
            ->setDescription('Export inline translations')
            ->addArgument('locale', InputOption::VALUE_REQUIRED, 'Locale')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Export filename')
            ->addOption('store', null, InputOption::VALUE_OPTIONAL, 'Limit to a special store');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $databaseHelper = $this->getDatabaseHelper();
        $pdo = $databaseHelper->getConnection();

        $filename = $input->getArgument('filename');

        if (!$filename) {
            $filename = 'translate.csv';
        }

        $locale = $input->getArgument('locale');
        $output->writeln('Exporting to <info>' . $filename . '</info>');

        $parameters = ['locale' => $locale];
        $sql = 'SELECT * FROM core_translate WHERE locale = :locale';
        if ($input->getOption('store')) {
            $sql .= ' AND store_id = :store_id';
            $parameters['store_id'] = Mage::app()->getStore($input->getOption('store'));
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetchAll();
        $fopen = fopen($filename, 'w');

        if ($result && $fopen) {
            foreach ($result as $row) {
                fputcsv($fopen, [$row['string'], $row['translate']]);
            }

            fclose($fopen);
        }

        return Command::SUCCESS;
    }
}
