<?php

namespace N98\Magento\Command\Customer;

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
    protected function configure()
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $dialog = $this->getQuestionHelper();

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $password = $dialog->ask($input, $output, $question);
        }

        // Firstname
        if (($firstname = $input->getArgument('firstname')) == null) {
            $firstname = $dialog->ask($input, $output, new Question('<question>Firstname:</question> '));
        }

        // Lastname
        if (($lastname = $input->getArgument('lastname')) == null) {
            $lastname = $dialog->ask($input, $output, new Question('<question>Lastname:</question> '));
        }

        $parameterHelper = $this->getParameterHelper();

        // Email
        $email = $parameterHelper->askEmail($input, $output);

        // Website
        $website = $parameterHelper->askWebsite($input, $output);

        // create new customer
        $customer = $this->getCustomerModel();
        $customer->setWebsiteId($website->getId());
        $customer->loadByEmail($email);

        $outputPlain = $input->getOption('format') === null;

        $table = [];
        if (!$customer->getId()) {
            $customer->setWebsiteId($website->getId());
            $customer->setEmail($email);
            $customer->setFirstname($firstname);
            $customer->setLastname($lastname);
            $customer->setPassword($password);

            $customer->save();
            $customer->setConfirmation(null);
            $customer->save();
            if ($outputPlain) {
                $output->writeln('<info>Customer <comment>' . $email . '</comment> successfully created</info>');
            } else {
                $table[] = [$email, $password, $firstname, $lastname];
            }
        } else {
            if ($outputPlain) {
                $output->writeln('<error>Customer ' . $email . ' already exists</error>');
            }
        }

        if (!$outputPlain) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['email', 'password', 'firstname', 'lastname'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
        return 0;
    }
}
