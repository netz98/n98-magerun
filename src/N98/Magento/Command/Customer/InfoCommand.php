<?php

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCustomerCommand
{
    /**
     * @var array
     */
    protected $blacklist = array(
        'password_hash',
        'increment_id',
    );

    protected function configure()
    {
        $this
            ->setName('customer:info')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website of the customer')
            ->setDescription('Show infos about a customre');
    }

    /**
     * @param \Symfony\Component\Console\Input\\Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\\Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $email = $this->getHelper('parameter')->askEmail($input, $output);
            $website = $this->getHelper('parameter')->askWebsite($input, $output);

            $customer = $this->getCustomerModel()
                ->setWebsiteId($website->getId())
                ->loadByEmail($email);
            if ($customer->getId() <= 0) {
                $output->writeln('<error>Customer was not found</error>');
                return;
            }

            $customer->load();
            $table = array();
            foreach ($customer->toArray() as $key => $value) {
                if (in_array($key, $this->blacklist)) {
                    continue;
                }
                try {
                    //$attribute = \Mage::getSingleton('eav/config')->getAttribute('customer', $key);
                    $attribute = $customer->getResource()->getAttribute($key);
                    $table[] = array(
                        'Attribute' => ($attribute instanceof \Mage_Customer_Model_Attribute ? $attribute->getFrontend()->getLabel() : $key),
                        'Value'     => ($attribute instanceof \Mage_Customer_Model_Attribute ? $attribute->getFrontend()->getValue($customer) : $value),
                    );
                } catch (\Exception $e) {
                    $table[] = array(
                        'Attribute' => $key,
                        'Value'     => $value,
                    );
                }
            }

            $this->getHelper('table')->write($output, $table);
        }
    }
}