<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Create customer command
 *
 * @package N98\Magento\Command\Customer
 */
class CreateCommand extends AbstractCustomerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('customer:create')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'Firstname')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'Lastname')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website')
            ->addFormatOption()
            ->setDescription('Creates a new customer/user for shop frontend.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $questionHelper = $this->getQuestionHelper();

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $password = $questionHelper->ask($input, $output, $question);
        }

        // Firstname
        if (($firstname = $input->getArgument('firstname')) == null) {
            $firstname = $questionHelper->ask($input, $output, new Question('<question>Firstname:</question> '));
        }

        // Lastname
        if (($lastname = $input->getArgument('lastname')) == null) {
            $lastname = $questionHelper->ask($input, $output, new Question('<question>Lastname:</question> '));
        }

        $parameterHelper = $this->getParameterHelper();

        // Email
        $email = $parameterHelper->askEmail($input, $output);

        // Website
        $website = $parameterHelper->askWebsite($input, $output);

        // create new customer
        $mageCustomerModelCustomer = $this->getCustomerModel();
        $mageCustomerModelCustomer->setWebsiteId((int) $website->getId());
        $mageCustomerModelCustomer->loadByEmail($email);

        $outputPlain = $input->getOption('format') === null;

        $table = [];
        if (!$mageCustomerModelCustomer->getId()) {
            $mageCustomerModelCustomer->setWebsiteId((int) $website->getId());
            $mageCustomerModelCustomer->setEmail($email);
            $mageCustomerModelCustomer->setFirstname($firstname);   # @phpstan-ignore method.notFound (missing in current OpenMage)
            $mageCustomerModelCustomer->setLastname($lastname);     # @phpstan-ignore method.notFound (missing in current OpenMage)
            $mageCustomerModelCustomer->setPassword($password);
            $mageCustomerModelCustomer->save();
            $mageCustomerModelCustomer->setConfirmation(null);
            $mageCustomerModelCustomer->save();
            if ($outputPlain) {
                $output->writeln('<info>Customer <comment>' . $email . '</comment> successfully created</info>');
            } else {
                $table[] = [$email, $password, $firstname, $lastname];
            }
        } elseif ($outputPlain) {
            $output->writeln('<error>Customer ' . $email . ' already exists</error>');
        }

        if (!$outputPlain) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['email', 'password', 'firstname', 'lastname'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        }

        return Command::SUCCESS;
    }
}
