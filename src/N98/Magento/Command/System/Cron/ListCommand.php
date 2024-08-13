<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function current;
use function is_array;

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
    protected static $defaultName = 'sys:cron:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all cronjobs.';

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
        if (is_array($this->data)) {
            return $this->data;
        }

        $this->data = $this->getJobs();
        return $this->data;
    }
}
