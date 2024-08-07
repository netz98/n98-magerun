<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandListable;

/**
 * List websites command
 *
 * @package N98\Magento\Command\System\Website
 */
class ListCommand extends AbstractMagentoCommand implements CommandListable
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
     * @return string
     */
    public function getSectionTitle(): string
    {
        return 'Magento Websites';
    }

    /**
     * @return string[]
     */
    public function getListHeader(): array
    {
        return ['id', 'code'];
    }

    /**
     * @return array
     */
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
