<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Attribute;
use Exception;
use Mage_Customer_Model_Attribute;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Customer info command
 *
 * @package N98\Magento\Command\Customer
 */
class InfoCommand extends AbstractCustomerCommand
{
    protected array $blacklist = ['password_hash', 'increment_id'];

    protected function configure(): void
    {
        $this
            ->setName('customer:info')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website of the customer')
            ->setDescription('Loads basic customer info by email address.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $parameterHelper = $this->getParameterHelper();

        $email = $parameterHelper->askEmail($input, $output);
        $website = $parameterHelper->askWebsite($input, $output);

        $customer = $this->getCustomerModel()
            ->setWebsiteId((int) $website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');
            return Command::FAILURE;
        }

        $table = [];
        foreach ($customer->toArray() as $key => $value) {
            if (in_array($key, $this->blacklist)) {
                continue;
            }

            try {
                $attribute = $customer->getResource()->getAttribute($key);
                $key = $attribute instanceof Mage_Customer_Model_Attribute ? $attribute->getFrontend()->getLabel() : $key;
                $value = $attribute instanceof Mage_Customer_Model_Attribute ? $attribute->getFrontend()->getValue($customer) : $value;

                if (is_array($value)) {
                    $value = implode(' - ', $value);
                }

                $table[] = [$key, $value];
            } catch (Exception $exception) {
                $table[] = [$key, $value];
            }
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders([Attribute::class, 'Value'])
            ->setRows($table)
            ->render($output);

        return Command::SUCCESS;
    }
}
