<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage;
use Mage_Core_Model_Store as Store;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List base urls command
 *
 * @package N98\Magento\Command\System\Store\Config
 */
class BaseUrlListCommand extends AbstractCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Stores - Base URLs';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:store:config:base-url:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all base urls.';

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
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

        return $this->data;
    }
}
