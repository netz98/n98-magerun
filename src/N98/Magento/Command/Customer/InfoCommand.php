<?php

namespace N98\Magento\Command\Customer;

use Attribute;
use Exception;
use Mage_Customer_Model_Attribute;
use N98\Util\Console\Helper\ParameterHelper;
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
    /**
     * @var array
     */
    protected $blacklist = ['password_hash', 'increment_id'];

    protected function configure()
    {
        $this
            ->setName('customer:info')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website of the customer')
            ->setDescription('Loads basic customer info by email address.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        /** @var ParameterHelper $parameterHelper */
        $parameterHelper = $this->getHelper('parameter');

        $email = $parameterHelper->askEmail($input, $output);
        $website = $parameterHelper->askWebsite($input, $output);

        $customer = $this->getCustomerModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');
            return 0;
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
            } catch (Exception $e) {
                $table[] = [$key, $value];
            }
        }

        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders([Attribute::class, 'Value'])
            ->setRows($table)
            ->render($output);
        return 0;
    }
}
