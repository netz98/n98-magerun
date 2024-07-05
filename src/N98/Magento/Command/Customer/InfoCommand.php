<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Core_Exception;
use Mage_Core_Model_Website;
use Mage_Customer_Model_Attribute;
use N98\Magento\Methods\Customer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function implode;
use function in_array;
use function is_array;

/**
 * Info customer command
 *
 * @package N98\Magento\Command\Customer
 */
class InfoCommand extends AbstractCustomerCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'customer:info';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Loads basic customer info by email address.';

    /**
     * @var array<int, string>
     */
    protected array $blacklist = ['password_hash', 'increment_id'];

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_EMAIL,
                InputArgument::OPTIONAL,
                'Customers email'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_WEBSITE,
                InputArgument::OPTIONAL,
                'Website of the customer'
            )
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Mage_Core_Exception
     */
    public function interact(InputInterface $input, OutputInterface $output): void
    {
        $parameterHelper = $this->getParameterHelper();

        // Email
        $email = $parameterHelper->askEmail($input, $output, self::COMMAND_ARGUMENT_EMAIL);
        $input->setArgument(self::COMMAND_ARGUMENT_EMAIL, $email);

        // Website
        $website = $parameterHelper->askWebsite($input, $output, self::COMMAND_ARGUMENT_WEBSITE);
        $input->setArgument(self::COMMAND_ARGUMENT_WEBSITE, $website);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Mage_Core_Exception
     *
     * @uses Customer\Customer::getModel()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument(self::COMMAND_ARGUMENT_EMAIL);
        /** @var Mage_Core_Model_Website $website */
        $website = $input->getArgument(self::COMMAND_ARGUMENT_WEBSITE);

        $customer = Customer\Customer::getModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');

            return Command::SUCCESS;
        }

        $table = [];
        foreach ($customer->toArray() as $key => $value) {
            if (in_array($key, $this->blacklist)) {
                continue;
            }
            try {
                $attribute = $customer->getResource()->getAttribute($key);
                $key = $attribute instanceof Mage_Customer_Model_Attribute
                    ? $attribute->getFrontend()->getLabel() : $key;
                $value = $attribute instanceof Mage_Customer_Model_Attribute
                    ? $attribute->getFrontend()->getValue($customer) : $value;

                if (is_array($value)) {
                    $value = implode(' - ', $value);
                }

                $table[] = [$key, $value];
            } catch (Exception $e) {
                $table[] = [$key, $value];
            }
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Attribute', 'Value'])
            ->setRows($table)
            ->render($output);

        return Command::SUCCESS;
    }
}
