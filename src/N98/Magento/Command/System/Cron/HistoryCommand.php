<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Store_Exception;
use Mage_Cron_Model_Schedule;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron history command
 *
 * @package N98\Magento\Command\Cron
 */
class HistoryCommand extends AbstractCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Last executed jobs';

    public const COMMAND_OPTION_TIMEZONE = 'timezone';

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
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * @throws Mage_Core_Model_Store_Exception
     * @throws Mage_Core_Exception
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            /** @var string $timezone */
            $timezone = $input->getOption(self::COMMAND_OPTION_TIMEZONE)
                ?: $this->_getMageStore()->getConfig('general/locale/timezone');

            $output->writeln(sprintf('<info>Times shown in <comment>%s</comment></info>', $timezone));

            $date = Mage::getSingleton('core/date');
            $offset = $date->calculateOffset($timezone);
            $collection = Mage::getModel('cron/schedule')->getCollection();
            $collection
                ->addFieldToFilter('status', ['neq' => Mage_Cron_Model_Schedule::STATUS_PENDING])
                ->addOrder('finished_at');

            /** @var Mage_Cron_Model_Schedule $job */
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
