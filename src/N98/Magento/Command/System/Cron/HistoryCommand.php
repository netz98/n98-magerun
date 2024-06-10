<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Store_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HistoryCommand extends AbstractMagentoCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Last executed jobs';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:cron:history';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Last executed cronjobs with status.';

    protected function configure()
    {
        $this->addOption(
            'timezone',
            null,
            InputOption::VALUE_OPTIONAL,
            'Timezone to show finished at in'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     * @throws Mage_Core_Exception
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $timezone = $input->getOption('timezone')
                ?: $this->_getMage()->getStore()->getConfig('general/locale/timezone');

            $output->writeln('<info>Times shown in <comment>' . $timezone . '</comment></info>');

            $date = Mage::getSingleton('core/date');
            $offset = $date->calculateOffset($timezone);
            $collection = Mage::getModel('cron/schedule')->getCollection();
            $collection
                ->addFieldToFilter('status', ['neq' => Mage_Cron_Model_Schedule::STATUS_PENDING])
                ->addOrder('finished_at');

            foreach ($collection as $job) {
                $this->data[] = [
                    'Job'       => $job->getJobCode(),
                    'Status'    => $job->getStatus(),
                    'Finished'  => $job->getFinishedAt() ? $date->gmtDate(
                        null,
                        $date->timestamp($job->getFinishedAt()) + $offset
                    ) : ''
                ];
            }
        }

        return $this->data;
    }
}
