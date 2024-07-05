<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage_Core_Exception;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron list command
 *
 * @package N98\Magento\Command\Cron
 */
class ListCommand extends AbstractCronCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Cronjobs';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:cron:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all cronjobs.';

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Job', 'Model'];
    }

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Exception
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = $this->getJobs();
        }

        return $this->data;
    }
}
