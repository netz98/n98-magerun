<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage_Customer_Model_Customer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List customer command
 *
 * @package N98\Magento\Command\Customer
 */
class ListCommand extends AbstractCustomerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('customer:list')
            ->addArgument('search', InputArgument::OPTIONAL, 'Search query')
            ->addFormatOption()
            ->setDescription('Lists customers')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searchs in firstname, lastname and email).
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $config = $this->getCommandConfig();

        $mageCustomerModelResourceCustomerCollection = $this->getCustomerCollection();
        $mageCustomerModelResourceCustomerCollection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname', 'website_id']);

        if ($input->getArgument('search')) {
            $mageCustomerModelResourceCustomerCollection->addAttributeToFilter(
                [['attribute' => 'email', 'like' => '%' . $input->getArgument('search') . '%'], ['attribute' => 'firstname', 'like' => '%' . $input->getArgument('search') . '%'], ['attribute' => 'lastname', 'like' => '%' . $input->getArgument('search') . '%']],
            );
        }

        $mageCustomerModelResourceCustomerCollection->setPageSize($config['limit']);

        $table = [];
        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($mageCustomerModelResourceCustomerCollection as $customer) {
            $table[] = [
                $customer->getId(),
                $customer->getEmail(),
                $customer->getFirstname(),
                $customer->getLastname(),
                $this->_getWebsiteCodeById((int) $customer->getWebsiteId()),
            ];
        }

        if ($table !== []) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['id', 'email', 'firstname', 'lastname', 'website'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        } else {
            $output->writeln('<comment>No customers found</comment>');
        }

        return Command::SUCCESS;
    }
}
