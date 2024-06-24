<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Core_Exception;
use Mage_Core_Model_Website;
use Mage_Customer_Model_Customer;
use Mage_Customer_Model_Resource_Customer_Collection;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
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
    public const COMMAND_ARGUMENT_ID = 'id';

    public const COMMAND_OPTION_ALL = 'all';

    public const COMMAND_OPTION_FORCE = 'force';

    public const COMMAND_OPTION_RANGE = 'range';

    /**
     * @var string
     */
    protected static $defaultName = 'customer:delete';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Deletes customers.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ID,
                InputArgument::OPTIONAL,
                'Customer Id or email',
                false
            )
            ->addOption(
                self::COMMAND_OPTION_ALL,
                'a', InputOption::VALUE_NONE,
                'Delete all customers'
            )
            ->addOption(
                self::COMMAND_OPTION_FORCE,
                'f', InputOption::VALUE_NONE,
                'Force delete'
            )
            ->addOption(
                self::COMMAND_OPTION_RANGE,
                '-r', InputOption::VALUE_NONE,
                'Delete a range of customers by Id'
            )
        ;
    }

    /**
     * @return string
     */
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dialog = $this->getQuestionHelper();

        /** @var string $id */
        $id = $input->getArgument(self::COMMAND_ARGUMENT_ID);
        $range = $input->getOption(self::COMMAND_OPTION_RANGE);
        $all = $input->getOption(self::COMMAND_OPTION_ALL);

        // Get args required
        if (!($id) && !($range) && !($all)) {
            // Delete more than one customer ?
            $batchDelete = $dialog->ask(
                $input,
                $output,
                $this->getQuestion('Delete more than 1 customer?', 'n'),
            );

            if ($batchDelete) {
                // Batch deletion
                $all = $dialog->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion('Delete all customers?', false, 'n'),
                );

                if (!$all) {
                    $range = $dialog->ask(
                        $input,
                        $output,
                        new ConfirmationQuestion('Delete a range of customers?', false, 'n'),
                    );

                    if (!$range) {
                        // Nothing to do
                        $output->writeln('<error>Finished nothing to do</error>');
                        return Command::SUCCESS;
                    }
                }
            }
        }

        if (!$range && !$all) {
            // Single customer deletion
            if (!$id) {
                /** @var string $id */
                $id = $dialog->ask($input, $output, $this->getQuestion('Customer Id'));
            }

            try {
                $customer = $this->getCustomer($input, $output, $id);
            } catch (Exception $e) {
                $output->writeln('<error>No customer found!</error>');
                return Command::SUCCESS;
            }

            if ($this->shouldRemove($input, $output)) {
                $this->deleteCustomer($output, $customer);
            } else {
                $output->writeln('<error>Aborting delete</error>');
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
                $ranges[0] = $dialog->ask(
                    $input,
                    $output,
                    $this->getQuestion('Range start Id', '1')->setValidator([$this, 'validateInt']),
                );
                $ranges[1] = $dialog->ask(
                    $input,
                    $output,
                    $this->getQuestion('Range end Id', '1')->setValidator([$this, 'validateInt']),
                );

                // Ensure ascending order
                sort($ranges);

                // Range delete, takes precedence over --all
                $customers->addAttributeToFilter('entity_id', ['from'  => $ranges[0], 'to'    => $ranges[1]]);
            }

            if ($this->shouldRemove($input, $output)) {
                $count = $this->batchDelete($output, $customers);
                $output->writeln('<info>Successfully deleted ' . $count . ' customer/s</info>');
            } else {
                $output->writeln('<error>Aborting delete</error>');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function shouldRemove(InputInterface $input, OutputInterface $output): bool
    {
        /** @var bool $shouldRemove */
        $shouldRemove = $input->getOption(self::COMMAND_OPTION_FORCE);
        if (!$shouldRemove) {
            /** @var bool $shouldRemove */
            $shouldRemove = $this->getQuestionHelper()->ask(
                $input,
                $output,
                $this->getQuestion('Are you sure?', 'n'),
            );
        }

        return $shouldRemove;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param int|string $id
     * @return Mage_Customer_Model_Customer
     * @throws Mage_Core_Exception
     */
    protected function getCustomer(InputInterface $input, OutputInterface $output, $id): Mage_Customer_Model_Customer
    {
        $customer = $this->getCustomerModel()->load($id);
        if (!$customer->getId()) {
            $parameterHelper = $this->getParameterHelper();
            /** @var Mage_Core_Model_Website $website */
            $website = $parameterHelper->askWebsite($input, $output);
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
     * @param OutputInterface $output
     * @param Mage_Customer_Model_Customer $customer
     * @return true|Exception
     * @throws Throwable
     */
    protected function deleteCustomer(OutputInterface $output, Mage_Customer_Model_Customer $customer)
    {
        try {
            $customer->delete();
            $output->writeln(sprintf(
                '<info>%s (%s) was successfully deleted</info>',
                $customer->getName(),
                $customer->getEmail()
            ));
            return true;
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return $e;
        }
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Customer_Model_Resource_Customer_Collection $customers
     * @return int
     * @throws Throwable
     */
    protected function batchDelete(
        OutputInterface $output,
        Mage_Customer_Model_Resource_Customer_Collection $customers
    ): int {
        $count = 0;

        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($customers as $customer) {
            if ($this->deleteCustomer($output, $customer) === true) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $answer
     * @return string
     */
    public function validateInt(string $answer): string
    {
        if ((int)$answer === 0) {
            throw new RuntimeException(
                'The range should be numeric and above 0 e.g. 1'
            );
        }

        return $answer;
    }

    /**
     * @param string $message
     * @param string|null $default [optional]
     * @return Question
     */
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
