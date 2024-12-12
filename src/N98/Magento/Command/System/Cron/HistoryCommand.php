<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage;
use Mage_Core_Model_Date;
use Mage_Core_Model_Store;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cronjob history command
 *
 * @package N98\Magento\Command\System\Cron
 */
class HistoryCommand extends AbstractMagentoCommand
{
    protected array $infos;

    protected function configure(): void
    {
        $this
            ->setName('sys:cron:history')
            ->setDescription('Last executed cronjobs with status.')
            ->addOption(
                'timezone',
                null,
                InputOption::VALUE_OPTIONAL,
                'Timezone to show finished at in',
            )
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Last executed jobs');
        }

        $this->initMagento();

        /** @var Mage_Core_Model_Store $store */
        $store = Mage::app()->getStore();

        $timezone = $input->getOption('timezone') ?: $store->getConfig('general/locale/timezone');
        $output->writeln('<info>Times shown in <comment>' . $timezone . '</comment></info>');

        /** @var Mage_Core_Model_Date $date */
        $date = Mage::getSingleton('core/date');
        $offset = $date->calculateOffset($timezone);

        /** @var Mage_Cron_Model_Schedule $model */
        $model = Mage::getModel('cron/schedule');
        $collection = $model->getCollection();
        $collection
            ->addFieldToFilter('status', ['neq' => Mage_Cron_Model_Schedule::STATUS_PENDING])
            ->addOrder('finished_at');

        $table = [];
        /** @var Mage_Cron_Model_Schedule $job */
        foreach ($collection as $job) {
            $table[] = [
                $job->getJobCode(),
                $job->getStatus(),
                $job->getFinishedAt() ? $date->gmtDate(null, $date->timestamp($job->getFinishedAt()) + $offset) : '',
            ];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Job', 'Status', 'Finished'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
