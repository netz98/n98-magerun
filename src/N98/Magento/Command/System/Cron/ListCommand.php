<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cronjob command
 *
 * @package N98\Magento\Command\System\Cron
 */
class ListCommand extends AbstractCronCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'sys:cron:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all cronjobs.';

    /**
     * @var array|null
     */
    public ?array $data = null;

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Cronjob List';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return array_keys(current($this->getListData($input, $output)));
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = $this->getJobs();
        }
        return $this->data;
    }
}
