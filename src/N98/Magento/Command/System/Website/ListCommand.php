<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List websites command
 *
 * @package 98\Magento\Command\System\Website
 */
class ListCommand extends AbstractCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Websites';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:website:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all websites.';

    /**
     * {@inheritDoc}
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];
        foreach ($this->_getMage()->getWebsites() as $store) {
            $storeId = (string) $store->getId();
            $this->data[$storeId] = [
                'id'    => $storeId,
                'code'  => $store->getCode()
            ];
        }

        ksort($this->data);
    }
}
