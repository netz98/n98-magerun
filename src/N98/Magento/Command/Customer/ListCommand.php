<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage_Core_Exception;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List customer command
 *
 * @package N98\Magento\Command\Customer
 */
class ListCommand extends AbstractCustomerCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Customers';

    public const COMMAND_ARGUMENT_SEARCH = 'search';

    protected const NO_DATA_MESSAGE = 'No customers found';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'customer:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists customers.';

    protected function configure()
    {
        $this->addArgument(
            self::COMMAND_ARGUMENT_SEARCH,
            InputArgument::OPTIONAL,
            'Search query'
        );

        parent::configure();
    }

    public function getHelp()
    {
        return <<<HELP
List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searches in firstname, lastname and email).
HELP;
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     * @throws Mage_Core_Exception
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
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

            foreach ($collection as $customer) {
                $this->data[] = [
                    'id'        => $customer->getId(),
                    'email'     => $customer->getEmail(),
                    'firstname' => $customer->getFirstname(),
                    'lastname'  => $customer->getLastname(),
                    'website'   => $this->_getWebsiteCodeById($customer->getwebsiteId())
                ];
            }
        }

        return $this->data;
    }
}
