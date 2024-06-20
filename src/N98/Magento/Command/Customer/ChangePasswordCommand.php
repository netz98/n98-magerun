<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Exception;
use Mage_Core_Exception;
use Mage_Core_Model_Website;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * Change password command
 *
 * @package N98\Magento\Command\Customer
 */
class ChangePasswordCommand extends AbstractCustomerCommand
{
    public const COMMAND_ARGUMENT_EMAIL = 'email';

    public const COMMAND_ARGUMENT_PASSWORD = 'password';

    public const COMMAND_ARGUMENT_WEBSITE = 'website';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'customer:change-password';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Changes the password of a customer.';

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
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
- Website parameter must only be given if more than one websites are available.
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
        $this->detectMagento($output);
        $this->initMagento();

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

        $customer = $this->getCustomerModel()
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
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return Command::SUCCESS;
    }
}
