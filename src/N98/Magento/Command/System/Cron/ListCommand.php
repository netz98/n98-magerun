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
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     * @throws Mage_Core_Exception
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = $this->getJobs();
    }
}
