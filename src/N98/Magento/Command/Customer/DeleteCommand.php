<?php

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Customer_Model_Entity_Customer_Collection;
use Mage_Customer_Model_Resource_Customer_Collection;
use N98\Util\Console\Helper\ParameterHelper;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteCommand
 * @package N98\Magento\Command\Customer
 */
class DeleteCommand extends AbstractCustomerCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var DialogHelper
     */
    protected $dialog;

    /**
     * Set up options
     */
    protected function configure()
    {
        $this
            ->setName('customer:delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'Customer Id or email', false)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Delete all customers')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete')
            ->addOption('range', '-r', InputOption::VALUE_NONE, 'Delete a range of customers by Id')
            ->setDescription('Delete Customer/s');

        $help = <<<HELP
This will delete a customer by a given Id/Email, delete all customers or delete all customers in a range of Ids.

<comment>Example Usage:</comment>

n98-magerun customer:delete 1                   <info># Will delete customer with Id 1</info>
n98-magerun customer:delete mike@example.com    <info># Will delete customer with that email</info>
n98-magerun customer:delete --all               <info># Will delete all customers</info>
n98-magerun customer:delete --range             <info># Will prompt for start and end Ids for batch deletion</info>

HELP;

        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return false|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $this->input = $input;
        $this->output = $output;
        /** @var DialogHelper dialog */
        $this->dialog = $this->getHelper('dialog');

        // Defaults
        $range = $all = false;

        $id = $this->input->getArgument('id');
        $range = $this->input->getOption('range');
        $all = $this->input->getOption('all');
        // Get args required
        if (!($id) && !($range) && !($all)) {

            // Delete more than one customer ?
            $batchDelete = $this->dialog->askConfirmation(
                $this->output,
                $this->getQuestion('Delete more than 1 customer?', 'n'),
                false
            );

            if ($batchDelete) {
                // Batch deletion
                $all = $this->dialog->askConfirmation(
                    $this->output,
                    $this->getQuestion('Delete all customers?', 'n'),
                    false
                );

                if (!$all) {
                    $range = $this->dialog->askConfirmation(
                        $this->output,
                        $this->getQuestion('Delete a range of customers?', 'n'),
                        false
                    );

                    if (!$range) {
                        // Nothing to do
                        $this->output->writeln('<error>Finished nothing to do</error>');
                        return false;
                    }
                }
            }
        }

        if (!$range && !$all) {
            // Single customer deletion
            if (!$id) {
                $id = $this->dialog->ask($this->output, $this->getQuestion('Customer Id'), null);
            }

            try {
                $customer = $this->getCustomer($id);
            } catch (Exception $e) {
                $this->output->writeln('<error>No customer found!</error>');
                return false;
            }

            if ($this->shouldRemove()) {
                $this->deleteCustomer($customer);
            } else {
                $this->output->writeln('<error>Aborting delete</error>');
            }
        } else {
            $customers = $this->getCustomerCollection();
            $customers
                ->addAttributeToSelect('firstname')
                ->addAttributeToSelect('lastname')
                ->addAttributeToSelect('email');

            if ($range) {
                // Get Range
                $ranges = array();
                $ranges[0] = $this->dialog->askAndValidate(
                    $this->output,
                    $this->getQuestion('Range start Id', '1'),
                    array($this, 'validateInt'),
                    false,
                    '1'
                );
                $ranges[1] = $this->dialog->askAndValidate(
                    $this->output,
                    $this->getQuestion('Range end Id', '1'),
                    array($this, 'validateInt'),
                    false,
                    '1'
                );

                // Ensure ascending order
                sort($ranges);

                // Range delete, takes precedence over --all
                $customers->addAttributeToFilter('entity_id', array(
                    'from'  => $ranges[0],
                    'to'    => $ranges[1],
                ));
            }

            if ($this->shouldRemove()) {
                $count = $this->batchDelete($customers);
                $this->output->writeln('<info>Successfully deleted ' . $count . ' customer/s</info>');
            } else {
                $this->output->writeln('<error>Aborting delete</error>');
            }
        }
    }

    /**
     * @return bool
     */
    protected function shouldRemove()
    {
        $shouldRemove = $this->input->getOption('force');
        if (!$shouldRemove) {
            $shouldRemove = $this->dialog->askConfirmation(
                $this->output,
                $this->getQuestion('Are you sure?', 'n'),
                false
            );
        }

        return $shouldRemove;
    }

    /**
     * @param int|string $id
     *
     * @return \Mage_Customer_Model_Customer
     * @throws RuntimeException
     */
    protected function getCustomer($id)
    {
        /** @var \Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomerModel()->load($id);
        if (!$customer->getId()) {
            /** @var $parameterHelper ParameterHelper */
            $parameterHelper = $this->getHelper('parameter');
            $website = $parameterHelper->askWebsite($this->input, $this->output);
            $customer = $this->getCustomerModel()
                ->setWebsiteId($website->getId())
                ->loadByEmail($id);
        }

        if (!$customer->getId()) {
            throw new RuntimeException('No customer found!');
        }

        return $customer;
    }

    /**
     * @param \Mage_Customer_Model_Customer $customer
     *
     * @return true|Exception
     */
    protected function deleteCustomer(\Mage_Customer_Model_Customer $customer)
    {
        try {
            $customer->delete();
            $this->output->writeln(
                sprintf('<info>%s (%s) was successfully deleted</info>', $customer->getName(), $customer->getEmail())
            );
            return true;
        } catch (Exception $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            return $e;
        }
    }

    /**
     * @param Mage_Customer_Model_Entity_Customer_Collection|Mage_Customer_Model_Resource_Customer_Collection $customers
     *
     * @return int
     */
    protected function batchDelete($customers)
    {
        $count = 0;
        foreach ($customers as $customer) {
            if ($this->deleteCustomer($customer) === true) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $answer
     * @return string
     */
    public function validateInt($answer)
    {
        if (intval($answer) === 0) {
            throw new RuntimeException(
                'The range should be numeric and above 0 e.g. 1'
            );
        }

        return $answer;
    }

    /**
     * @param string $message
     * @param string $default [optional]
     *
     * @return string
     */
    private function getQuestion($message, $default = null)
    {
        $params = array($message);
        $pattern = '%s: ';

        if (null !== $default) {
            $params[] = $default;
            $pattern .= '[%s] ';
        }

        return vsprintf($pattern, $params);
    }
}
