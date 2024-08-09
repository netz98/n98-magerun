<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List websites command
 *
 * @package N98\Magento\Command\System\Website
 */
class ListCommand extends AbstractMagentoCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'sys:website:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all websites.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Magento Websites';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'code'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $table = [];
        foreach (Mage::app()->getWebsites() as $store) {
            $table[$store->getId()] = [
                $store->getId(),
                $store->getCode()
            ];
        }

        ksort($table);

        return $table;
    }
}
