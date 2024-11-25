<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List websites command
 *
 * @package N98\Magento\Command\System\Website
 */
class ListCommand extends AbstractMagentoCommand
{
    protected array $infos;

    protected function configure(): void
    {
        $this
            ->setName('sys:website:list')
            ->setDescription('Lists all websites')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];
        $this->detectMagento($output);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Magento Websites');
        }

        $this->initMagento();

        foreach (Mage::app()->getWebsites() as $website) {
            $table[$website->getId()] = [$website->getId(), $website->getCode()];
        }

        ksort($table);

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['id', 'code'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
