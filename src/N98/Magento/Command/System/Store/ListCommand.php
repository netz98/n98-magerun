<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_null;
use function ksort;

/**
 * List stores command
 *
 * @package N98\Magento\Command\System\Store
 */
class ListCommand extends AbstractCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Stores';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:store:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all installed store-views';

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['ID', 'Code'];
    }

    /**
     * {@inheritdoc}
     *
     * @uses Mage::app()
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
                    $store->getCode()
                ];
            }

            ksort($this->data);
        }

        return $this->data;
    }
}
