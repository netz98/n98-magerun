<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        foreach ($this->_getMage()->getStores() as $store) {
            $storeId = (string) $store->getId();
            $this->data[$storeId] = [
                'id'    => $storeId,
                'code'  => $store->getCode()
            ];
        }

        ksort($this->data);
    }
}
