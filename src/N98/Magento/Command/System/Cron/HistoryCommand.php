<?php

namespace N98\Magento\Command\System\Cron;

use Mage;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Data_Collection_Db;

/**
 * List cronjob history command
 *
 * @package N98\Magento\Command\System\Cron
 */
class HistoryCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:cron:history')
            ->setDescription('Last executed cronjobs with status.')
            ->addOption(
                'timezone',
                null,
                InputOption::VALUE_OPTIONAL,
                'Timezone to show finished at in'
            )
            ->addFormatOption()
        ;
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

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Last executed jobs');
        }
        $this->initMagento();

        $timezone = $input->getOption('timezone') ?: Mage::app()->getStore()->getConfig('general/locale/timezone');

        $output->writeln('<info>Times shown in <comment>' . $timezone . '</comment></info>');

        $date = Mage::getSingleton('core/date');
        $offset = $date->calculateOffset($timezone);
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $collection
            ->addFieldToFilter('status', ['neq' => Mage_Cron_Model_Schedule::STATUS_PENDING])
            ->addOrder('finished_at', Varien_Data_Collection_Db::SORT_ORDER_DESC);

        $table = [];
        foreach ($collection as $job) {
            $table[] = [$job->getJobCode(), $job->getStatus(), $job->getFinishedAt() ? $date->gmtDate(null, $date->timestamp($job->getFinishedAt()) + $offset) : ''];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Job', 'Status', 'Finished'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
