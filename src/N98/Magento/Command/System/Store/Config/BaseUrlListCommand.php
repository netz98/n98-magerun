<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage;
use Mage_Core_Model_Store as Store;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        foreach ($this->_getMage()->getStores() as $store) {
            $storeId = (string) $store->getId();
            $this->data[$storeId] = [
                'ID'                => $storeId,
                'Code'              => $store->getCode(),
                'Unsecure base URL' => Mage::getStoreConfig(Store::XML_PATH_UNSECURE_BASE_URL, $store),
                'Secure base URL'   => Mage::getStoreConfig(Store::XML_PATH_SECURE_BASE_URL, $store)
            ];
        }

        ksort($this->data);
    }
}
