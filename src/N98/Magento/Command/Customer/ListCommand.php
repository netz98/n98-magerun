<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage_Core_Exception;
use Mage_Customer_Model_Customer;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List customer command
 *
 * @package N98\Magento\Command\Customer
 */
class ListCommand extends AbstractCustomerCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Customer list';

    public const COMMAND_ARGUMENT_SEARCH = 'search';

    protected const NO_DATA_MESSAGE = 'No customers found';

    /**
     * @var string
     */
    protected static $defaultName = 'customer:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists customers.';

    protected function configure(): void
    {
        $this->addArgument(
            self::COMMAND_ARGUMENT_SEARCH,
            InputArgument::OPTIONAL,
            'Search query'
        );

        parent::configure();
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searches in firstname, lastname and email).
HELP;
    }

    /**
     * {@inheritDoc}
     * @throws Mage_Core_Exception
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];

        $config = $this->getCommandConfig();

        $collection = $this->getCustomerCollection();
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname', 'website_id']);

        $search = $input->getArgument(self::COMMAND_ARGUMENT_SEARCH);
        if ($search) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'email', 'like' => '%' . $search . '%'],
                    ['attribute' => 'firstname', 'like' => '%' . $search . '%'],
                    ['attribute' => 'lastname', 'like' => '%' . $search . '%']
                ]
            );
        }

        $collection->setPageSize($config['limit']);

        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($collection as $customer) {
            $this->data[] = [
                'id'        => $customer->getId(),
                'email'     => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname'  => $customer->getLastname(),
                'website'   => $this->_getWebsiteCodeById((int)$customer->getwebsiteId())
            ];
        }
    }
}
