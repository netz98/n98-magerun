<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;
use function ksort;

/**
 * List stores base url command
 *
 * @package N98\Magento\Command\System\Store\Config
 */
class BaseUrlListCommand extends AbstractMagentoCommand implements CommandFormatable
{
    /**
     * @var string
     */
    protected static $defaultName = 'sys:store:config:base-url:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all base urls.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Magento Stores - Base URLs';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'code', 'unsecure_baseurl', 'secure_baseurl'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        $table = [];
        foreach (Mage::app()->getStores() as $store) {
            $table[$store->getId()] = [
                $store->getId(),
                $store->getCode(),
                Mage::getStoreConfig('web/unsecure/base_url', $store),
                Mage::getStoreConfig('web/secure/base_url', $store)
            ];
        }

        ksort($table);
        $this->data = $table;

        return $this->data;
    }
}
