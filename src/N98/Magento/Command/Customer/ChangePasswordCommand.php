<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Core_Exception;
use Mage_Core_Model_Website;
use N98\Magento\Methods\Customer;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

use function implode;
use function is_array;

/**
 * Change password command
 *
 * @package N98\Magento\Command\Customer
 */
class ChangePasswordCommand extends AbstractCustomerCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'customer:change-password';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Changes the password of a customer.';

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_EMAIL,
                InputArgument::OPTIONAL,
                'Email'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_PASSWORD,
                InputArgument::OPTIONAL,
                'Password'
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
     */
    public function getHelp(): string
    {
        return <<<HELP
- Website parameter must only be given if more than one websites are available.
HELP;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Mage_Core_Exception
     * @throws Throwable
     *
     * @uses Customer\Customer::getModel()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (($password = $input->getArgument(self::COMMAND_ARGUMENT_PASSWORD)) == null) {
            $dialog = $this->getQuestionHelper();
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $password = $dialog->ask($input, $output, $question);
        }

        $parameterHelper = $this->getParameterHelper();

        $email = $parameterHelper->askEmail($input, $output);
        /** @var Mage_Core_Model_Website $website */
        $website = $parameterHelper->askWebsite($input, $output);

        $customer = Customer\Customer::getModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');

            return Command::SUCCESS;
        }

        try {
            $result = $customer->validate();
            if (is_array($result)) {
                throw new RuntimeException(implode(PHP_EOL, $result));
            }
            $customer->setPassword($password);
            $customer->save();
            $output->writeln('<info>Password successfully changed</info>');
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
