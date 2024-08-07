<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandListable;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * List websites command
 *
 * @package N98\Magento\Command\System\Website
 */
#[AsCommand(
    name: 'sys:website:list',
    description: 'Lists all websites.'
)]
class ListCommand extends AbstractMagentoCommand implements CommandListable
{
    public function getSectionTitle(): string
    {
        return 'Magento Websites';
    }

    public function getListHeader(): array
    {
        return ['id', 'code'];
    }

    public function getListData(): array
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
