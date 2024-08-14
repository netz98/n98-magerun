<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage_Core_Exception;
use Mage_Customer_Model_Customer;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;

/**
 * List customer command
 *
 * @package N98\Magento\Command\Customer
 */
class ListCommand extends AbstractCustomerCommand implements CommandFormatable
{
    public const COMMAND_ARGUMENT_SEARCH = 'search';

    /**
     * @var string
     */
    protected static $defaultName = 'customer:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists customers.';

    /**
     * @var string
     */
    protected static string $noResultMessage = 'No customers found.';

    /**
     * {@inheritDoc}
     */
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
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Customer list';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'email', 'firstname', 'lastname', 'website'];
    }

    /**
     * {@inheritDoc}
     * @throws Mage_Core_Exception
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

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

        $this->data = [];
        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($collection as $customer) {
            $this->data[] = [
                $customer->getId(),
                $customer->getEmail(),
                $customer->getFirstname(),
                $customer->getLastname(),
                $this->_getWebsiteCodeById($customer->getwebsiteId())
            ];
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searches in firstname, lastname and email).
HELP;
    }
}
