<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cronjob command
 *
 * @package N98\Magento\Command\System\Cron
 */
class ListCommand extends AbstractCronCommand
{
    protected array $infos;

    protected function configure(): void
    {
        $this
            ->setName('sys:cron:list')
            ->setDescription('Lists all cronjobs')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Cronjob List');
        }

        $this->initMagento();

        $table = $this->getJobs();

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(array_keys(current($table)))
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
