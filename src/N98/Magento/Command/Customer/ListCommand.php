<?php

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:list')
            ->addArgument('search', InputArgument::OPTIONAL, 'Search query')
            ->setDescription('Lists customers')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $config = $this->getCommandConfig();

            $collection = $this->getCustomerCollection();
            $collection->addAttributeToSelect(array('entity_id', 'email', 'firstname', 'lastname', 'website_id'));

            if ($input->getArgument('search')) {
                $collection->addAttributeToFilter(
                    array(
                        array('attribute' => 'email', 'like' => '%' . $input->getArgument('search') . '%'),
                        array('attribute' => 'firstname', 'like' => '%' . $input->getArgument('search') . '%'),
                        array('attribute' => 'lastname', 'like' => '%' . $input->getArgument('search') . '%'),
                    )
                );
            }

            $collection->setPageSize($config['limit']);

            $table = array();
            foreach ($collection as $customer) {
                $table[] = array(
                    'id'        => $customer->getId(),
                    'email'     => $customer->getEmail(),
                    'firstname' => $customer->getFirstname(),
                    'lastname'  => $customer->getLastname(),
                    'website'   => $this->_getWebsiteCodeById($customer->getwebsiteId()),
                );
            }

            if (count($table) > 0) {
                $this->getHelper('table')->write($output, $table);
            } else {
                $output->writeln('<comment>No customers found</comment>');
            }
        }
    }
}