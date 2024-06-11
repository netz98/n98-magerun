<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        return $this->getJobs();
    }
}
