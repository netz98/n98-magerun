<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use Mage_Core_Exception;
use Mage_Core_Model_Config_Element;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron list command
 *
 * @package N98\Magento\Command\Cron
 */
class ListCommand extends AbstractCronCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Cronjobs';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:cron:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all cronjobs.';

    /**
     * {@inheritdoc}
     * @return array<string, array<string, Mage_Core_Model_Config_Element|string|null>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     * @throws Mage_Core_Exception
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        return $this->getJobs();
    }
}
