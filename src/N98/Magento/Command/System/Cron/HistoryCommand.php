<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Store_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cronjob history command
 *
 * @package N98\Magento\Command\System\Cron
 */
class HistoryCommand extends AbstractMagentoCommand implements CommandFormatable
{
    public const COMMAND_OPTION_TIMEZONE = 'timezone';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:cron:history';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Last executed cronjobs with status.';

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            self::COMMAND_OPTION_TIMEZONE,
            null,
            InputOption::VALUE_OPTIONAL,
            'Timezone to show finished at in'
        );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Last executed jobs';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['Job', 'Status', 'Finished'];
    }

    /**
     * {@inheritDoc}
     * @throws Mage_Core_Model_Store_Exception|Mage_Core_Exception
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $timezone = $input->getOption(self::COMMAND_OPTION_TIMEZONE)
            ?: Mage::app()->getStore()->getConfig('general/locale/timezone');

        $output->writeln(sprintf('<info>Times shown in <comment>%s</comment></info>', $timezone));

        $date = Mage::getSingleton('core/date');
        $offset = $date->calculateOffset($timezone);
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $collection
            ->addFieldToFilter('status', ['neq' => Mage_Cron_Model_Schedule::STATUS_PENDING])
            ->addOrder('finished_at');

        $table = [];
        /** @var Mage_Cron_Model_Schedule $job */
        foreach ($collection as $job) {
            $table[] = [
                $job->getJobCode(),
                $job->getStatus(),
                $job->getFinishedAt() ? $date->gmtDate(
                    null,
                    $date->timestamp($job->getFinishedAt()) + $offset
                ) : ''
            ];
        }

        return $table;
    }
}
