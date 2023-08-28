<?php

namespace N98\Magento\Command\Customer;

use Mage_Customer_Model_Attribute;
use Attribute;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $email = $this->getHelper('parameter')->askEmail($input, $output);
        $website = $this->getHelper('parameter')->askWebsite($input, $output);

        $customer = $this->getCustomerModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');
            return 0;
        }

        $customer->load();
        $table = [];
        foreach ($customer->toArray() as $key => $value) {
            if (in_array($key, $this->blacklist)) {
                continue;
            }
            try {
                $attribute = $customer->getResource()->getAttribute($key);
                $table[] = [$attribute instanceof Mage_Customer_Model_Attribute
                    ? $attribute->getFrontend()->getLabel() : $key, $attribute instanceof Mage_Customer_Model_Attribute
                    ? $attribute->getFrontend()->getValue($customer) : $value];
            } catch (Exception $e) {
                $table[] = [$key, $value];
            }
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders([Attribute::class, 'Value'])
            ->setRows($table)
            ->render($output);
        return 0;
    }
}
