<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Core_Exception;
use Mage_Customer_Model_Customer;
use Mage_Customer_Model_Entity_Customer_Collection;
use Mage_Customer_Model_Resource_Customer_Collection;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * Delete customer command
 *
 * @package N98\Magento\Command\Customer
 */
class DeleteCommand extends AbstractCustomerCommand
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected QuestionHelper $questionHelper;

    /**
     * Set up options
     */
    protected function configure(): void
    {
        $this
            ->setName('customer:delete')
            ->addArgument('id', InputArgument::OPTIONAL, 'Customer Id or email', false)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Delete all customers')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete')
            ->addOption('range', '-r', InputOption::VALUE_NONE, 'Delete a range of customers by Id')
            ->setDescription('Delete Customer/s');
    }

    public function getHelp(): string
    {
        return <<<HELP
This will delete a customer by a given Id/Email, delete all customers or delete all customers in a range of Ids.

<comment>Example Usage:</comment>

n98-magerun customer:delete 1                   <info># Will delete customer with Id 1</info>
n98-magerun customer:delete mike@example.com    <info># Will delete customer with that email</info>
n98-magerun customer:delete --all               <info># Will delete all customers</info>
n98-magerun customer:delete --range             <info># Will prompt for start and end Ids for batch deletion</info>
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $this->getQuestionHelper();
        // Defaults
        $range = false;
        $all = false;

        $id = $this->input->getArgument('id');
        $range = $this->input->getOption('range');
        $all = $this->input->getOption('all');
        // Get args required
        if (!($id) && !($range) && !($all)) {
            // Delete more than one customer ?
            $batchDelete = $this->questionHelper->ask(
                $this->input,
                $this->output,
                $this->getQuestion('Delete more than 1 customer?', 'n'),
            );

            if ($batchDelete) {
                // Batch deletion
                $all = $this->questionHelper->ask(
                    $this->input,
                    $this->output,
                    new ConfirmationQuestion('Delete all customers?', false),
                );

                if (!$all) {
                    $range = $this->questionHelper->ask(
                        $this->input,
                        $this->output,
                        new ConfirmationQuestion('Delete a range of customers?', false),
                    );

                    if (!$range) {
                        // Nothing to do
                        $this->output->writeln('<error>Finished nothing to do</error>');
                        return (int) false;
                    }
                }
            }
        }

        if (!$range && !$all) {
            // Single customer deletion
            if (!$id) {
                $id = $this->questionHelper->ask($this->input, $this->output, $this->getQuestion('Customer Id'));
            }

            try {
                $customer = $this->getCustomer($id);
            } catch (Exception $exception) {
                $this->output->writeln('<error>No customer found!</error>');
                return (int) false;
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
                $ranges = [];
                $ranges[0] = $this->questionHelper->ask(
                    $this->input,
                    $this->output,
                    $this->getQuestion('Range start Id', '1')->setValidator([$this, 'validateInt']),
                );
                $ranges[1] = $this->questionHelper->ask(
                    $this->input,
                    $this->output,
                    $this->getQuestion('Range end Id', '1')->setValidator([$this, 'validateInt']),
                );

                // Ensure ascending order
                sort($ranges);

                // Range delete, takes precedence over --all
                $customers->addAttributeToFilter('entity_id', ['from'  => $ranges[0], 'to'    => $ranges[1]]);
            }

            if ($this->shouldRemove()) {
                $count = $this->batchDelete($customers);
                $this->output->writeln('<info>Successfully deleted ' . $count . ' customer/s</info>');
            } else {
                $this->output->writeln('<error>Aborting delete</error>');
            }
        }

        return Command::SUCCESS;
    }

    protected function shouldRemove(): bool
    {
        $shouldRemove = $this->input->getOption('force');
        if (!$shouldRemove) {
            return $this->questionHelper->ask(
                $this->input,
                $this->output,
                $this->getQuestion('Are you sure?', 'n'),
            );
        }

        return $shouldRemove;
    }

    /**
     * @param int|string $id
     *
     * @throws RuntimeException|Mage_Core_Exception
     */
    protected function getCustomer($id): Mage_Customer_Model_Customer
    {
        $customer = $this->getCustomerModel()->load($id);
        if (!$customer->getId() && is_string($id)) {
            $parameterHelper = $this->getParameterHelper();
            $website = $parameterHelper->askWebsite($this->input, $this->output);
            $customer = $this->getCustomerModel()
                ->setWebsiteId((int) $website->getId())
                ->loadByEmail($id);
        }

        if (!$customer->getId()) {
            throw new RuntimeException('No customer found!');
        }

        return $customer;
    }

    /**
     * @return true|Exception
     * @throws Throwable
     */
    protected function deleteCustomer(Mage_Customer_Model_Customer $mageCustomerModelCustomer)
    {
        try {
            $mageCustomerModelCustomer->delete();
            $this->output->writeln(
                sprintf('<info>%s (%s) was successfully deleted</info>', $mageCustomerModelCustomer->getName(), $mageCustomerModelCustomer->getEmail()),
            );
            return true;
        } catch (Exception $exception) {
            $this->output->writeln('<error>' . $exception->getMessage() . '</error>');
            return $exception;
        }
    }

    /**
     * @param Mage_Customer_Model_Entity_Customer_Collection|Mage_Customer_Model_Resource_Customer_Collection $customers
     */
    protected function batchDelete($customers): int
    {
        $count = 0;
        foreach ($customers as $customer) {
            if ($this->deleteCustomer($customer) === true) {
                ++$count;
            }
        }

        return $count;
    }

    public function validateInt(string $answer): string
    {
        if ((int) $answer === 0) {
            throw new RuntimeException(
                'The range should be numeric and above 0 e.g. 1',
            );
        }

        return $answer;
    }

    private function getQuestion(string $message, ?string $default = null): Question
    {
        $params = [$message];
        $pattern = '%s: ';

        if (null !== $default) {
            $params[] = $default;
            $pattern .= '[%s] ';
        }

        return new Question(vsprintf($pattern, $params));
    }
}
