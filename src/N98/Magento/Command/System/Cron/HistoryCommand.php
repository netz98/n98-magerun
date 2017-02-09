<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Last executed jobs');
        }
        $this->initMagento();

        $timezone = $input->getOption('timezone')
            ? $input->getOption('timezone') : \Mage::app()->getStore()->getConfig('general/locale/timezone');

        $output->writeln('<info>Times shown in <comment>' . $timezone . '</comment></info>');

        $date = \Mage::getSingleton('core/date');
        $offset = $date->calculateOffset($timezone);
        $collection = \Mage::getModel('cron/schedule')->getCollection();
        $collection
            ->addFieldToFilter('status', array('neq' => \Mage_Cron_Model_Schedule::STATUS_PENDING))
            ->addOrder('finished_at', \Varien_Data_Collection_Db::SORT_ORDER_DESC);

        $table = array();
        foreach ($collection as $job) {
            $table[] = array(
                $job->getJobCode(),
                $job->getStatus(),
                $job->getFinishedAt() ? $date->gmtDate(null, $date->timestamp($job->getFinishedAt()) + $offset) : '',
            );
        }
        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('Job', 'Status', 'Finished'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
