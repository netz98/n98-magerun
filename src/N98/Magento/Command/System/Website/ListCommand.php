<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Website;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List websites command
 *
 * @package 98\Magento\Command\System\Website
 */
class ListCommand extends AbstractCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Websites';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'sys:website:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all websites.';

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
            foreach ($this->_getMage()->getWebsites() as $store) {
                $storeId = (string) $store->getId();
                $this->data[$storeId] = [
                    'id'    => $storeId,
                    'code'  => $store->getCode()
                ];
            }

            ksort($this->data);
        }

        return $this->data;
    }
}
