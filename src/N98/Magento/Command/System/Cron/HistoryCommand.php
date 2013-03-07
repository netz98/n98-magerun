<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription('Lists last executed jobs');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Last executed jobs');
        $this->initMagento();

        $collection = \Mage::getModel('cron/schedule')->getCollection();
        $collection->addFieldToFilter('status', array('neq' => \Mage_Cron_Model_Schedule::STATUS_PENDING))
                   ->addOrder('finished_at', \Varien_Data_Collection_Db::SORT_ORDER_DESC);

        foreach ($collection as $job) {
            $table[] = array(
                'Job'      => $job->getJobCode(),
                'Status'   => $job->getStatus(),
                'Finished' => $job->getFinishedAt()
            );
        }

        $this->getHelper('table')->write($output, $table);
    }
}