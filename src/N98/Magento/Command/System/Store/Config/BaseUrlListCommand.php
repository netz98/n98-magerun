<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage;
use Mage_Core_Model_Store as Store;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List base urls command
 *
 * @package N98\Magento\Command\System\Store\Config
 */
class BaseUrlListCommand extends AbstractMagentoCommand implements AbstractMagentoCommandFormatInterface
{
    public const COMMAND_SECTION_TITLE_TEXT = 'Stores - Base URLs';

    /**
     * @var array<int|string, array<int|string, mixed>>|null
     */
    private ?array $data = null;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array<int|string, array<int|string, mixed>>
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
