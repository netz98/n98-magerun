<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Locale;
use Mage;
use Mage_Core_Model_Store_Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export translations command
 *
 * @package NN98\Magento\Command\Developer\Translate
 */
class ExportCommand extends AbstractMagentoCommand
{
    public const COMMAND_ARGUMENT_LOCALE = 'locale';

    public const COMMAND_ARGUMENT_FILENAME = 'filename';

    public const COMMAND_OPTION_STORE = 'store';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:translate:export';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Export inline translations.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_LOCALE,
                InputOption::VALUE_REQUIRED,
                Locale::class
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_FILENAME,
                InputArgument::OPTIONAL,
                'Export filename'
            )
            ->addOption(
                self::COMMAND_OPTION_STORE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit to a special store'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $helper = $this->getDatabaseHelper();
        $db = $helper->getConnection();

        /** @var string $filename */
        $filename = $input->getArgument(self::COMMAND_ARGUMENT_FILENAME);

        if (!$filename) {
            $filename = 'translate.csv';
        }

        $locale = $input->getArgument(self::COMMAND_ARGUMENT_LOCALE);
        $output->writeln('Exporting to <info>' . $filename . '</info>');

        /** @var string $store */
        $store = $input->getOption(self::COMMAND_OPTION_STORE);
        $parameters = ['locale' => $locale];
        $sql = "SELECT * FROM core_translate WHERE locale = :locale";
        if ($store) {
            $sql .= ' AND store_id = :store_id';
            $parameters['store_id'] = Mage::app()->getStore($store);
        }
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        $result = $statement->fetchAll();

        if ($result) {
            $f = fopen($filename, 'w');
            if ($f) {
                foreach ($result as $row) {
                    fputcsv($f, [$row['string'], $row['translate']]);
                }

                fclose($f);
            }
        }

        return Command::SUCCESS;
    }
}
