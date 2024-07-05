<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage_Core_Model_Store as Store;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_null;
use function ksort;

/**
 * List base urls command
 *
 * @package N98\Magento\Command\System\Store\Config
 */
class BaseUrlListCommand extends AbstractCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Stores - Base URLs';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:store:config:base-url:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all base urls.';

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['ID', 'Code', 'Unsecure base URL', 'Secure base URL'];
    }

    /**
     * {@inheritdoc}
     *
     * @uses Mage::app()
     * @uses Mage::getStoreConfigAsString()
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];
            foreach (Mage::app()->getStores() as $store) {
                $storeId = (string) $store->getId();
                $this->data[$storeId] = [
                    $storeId,
                    $store->getCode(),
                    Mage::getStoreConfigAsString(Store::XML_PATH_UNSECURE_BASE_URL, $store),
                    Mage::getStoreConfigAsString(Store::XML_PATH_SECURE_BASE_URL, $store)
                ];
            }

            ksort($this->data);
        }

        return $this->data;
    }
}
