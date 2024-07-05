<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage_Core_Exception;
use Mage_Core_Model_Website;
use N98\Magento\Methods\Customer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Throwable;

use function is_null;
use function sprintf;

/**
 * Create customer command
 *
 * @package N98\Magento\Command\Customer
 */
class CreateCommand extends AbstractCustomerCommand
{
    public const COMMAND_ARGUMENT_FIRSTNAME = 'firstname';

    public const COMMAND_ARGUMENT_LASTNAME = 'lastname';

    /**
     * @var string
     */
    protected static $defaultName = 'customer:create';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Creates a new customer/user for shop frontend.';

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
                self::COMMAND_ARGUMENT_FIRSTNAME,
                InputArgument::OPTIONAL,
                'Firstname'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_LASTNAME,
                InputArgument::OPTIONAL,
                'Lastname'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_WEBSITE,
                InputArgument::OPTIONAL,
                'Website'
            )
            ->addFormatOption()
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Mage_Core_Exception
     */
    public function interact(InputInterface $input, OutputInterface $output): void
    {
        $parameterHelper = $this->getParameterHelper();

        // Email
        $email = $parameterHelper->askEmail($input, $output, self::COMMAND_ARGUMENT_EMAIL);
        $input->setArgument(self::COMMAND_ARGUMENT_EMAIL, $email);

        // password
        $password = $parameterHelper->askPassword($input, $output, self::COMMAND_ARGUMENT_PASSWORD);
        $input->setArgument(self::COMMAND_ARGUMENT_PASSWORD, $password);

        // Firstname
        $firstname = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_FIRSTNAME, $input, $output);
        $input->setArgument(self::COMMAND_ARGUMENT_FIRSTNAME, $firstname);

        // Lastname
        $lastname = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_LASTNAME, $input, $output);
        $input->setArgument(self::COMMAND_ARGUMENT_LASTNAME, $lastname);

        // Website
        $website = $parameterHelper->askWebsite($input, $output, self::COMMAND_ARGUMENT_WEBSITE);
        $input->setArgument(self::COMMAND_ARGUMENT_WEBSITE, $website);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument(self::COMMAND_ARGUMENT_EMAIL);
        /** @var string $password */
        $password = $input->getArgument(self::COMMAND_ARGUMENT_PASSWORD);
        /** @var string $firstname */
        $firstname = $input->getArgument(self::COMMAND_ARGUMENT_FIRSTNAME);
        /** @var string $lastname */
        $lastname = $input->getArgument(self::COMMAND_ARGUMENT_LASTNAME);
        /** @var Mage_Core_Model_Website $website */
        $website = $input->getArgument(self::COMMAND_ARGUMENT_WEBSITE);

        $this->saveCustomer($email, $password, $firstname, $lastname, $website);

        if (is_null($input->getOption(self::COMMAND_OPTION_FORMAT))) {
            $output->writeln(sprintf(
                '<info>Customer <comment>%s</comment> successfully created</info>',
                $email
            ));

            return Command::SUCCESS;
        }

        $this->data[] = [
            'email'     => $email,
            'password'  => $password,
            'firstname' => $firstname,
            'lastname'  => $lastname
        ];

        return parent::execute($input, $output);
    }

    /**
     * Create new customer
     *
     * @param string $email
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     * @param Mage_Core_Model_Website $website
     *
     * @return void
     *
     * @uses Customer\Customer::getModel()
     */
    private function saveCustomer(
        string $email,
        string $password,
        string $firstname,
        string $lastname,
        Mage_Core_Model_Website $website
    ): void {
        $customer = Customer\Customer::getModel();
        $customer->setWebsiteId($website->getId());

        try {
            $customer->loadByEmail($email);
        } catch (Mage_Core_Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }

        if ($customer->getId()) {
            throw new RuntimeException(sprintf('Customer %s already exists', $email));
        }

        $customer->setWebsiteId($website->getId());
        $customer->setEmail($email);
        $customer->setFirstname($firstname);
        $customer->setLastname($lastname);
        $customer->setPassword($password);

        try {
            $customer->save();
            $customer->setConfirmation(null);
            $customer->save();
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
