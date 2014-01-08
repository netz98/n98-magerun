<?php

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Creates a new customer/user for shop frontend.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $dialog = $this->getHelperSet()->get('dialog');

            // Email
            $email = $this->getHelperSet()->get('parameter')->askEmail($input, $output);

            // Password
            $password = $this->getHelperSet()->get('parameter')->askPassword($input, $output, 'password', false);

            // Firstname
            if (($firstname = $input->getArgument('firstname')) == null) {
                $firstname = $dialog->ask($output, '<question>Firstname:</question>');
            }

            // Lastname
            if (($lastname = $input->getArgument('lastname')) == null) {
                $lastname = $dialog->ask($output, '<question>Lastname:</question>');
            }

            $website = $this->getHelperSet()->get('parameter')->askWebsite($input, $output);

            // create new customer
            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($website->getId());
            $customer->loadByEmail($email);

            if (!$customer->getId()) {
                $customer->setWebsiteId($website->getId());
                $customer->setEmail($email);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setPassword($password);

                $customer->save();
                $customer->setConfirmation(null);
                $customer->save();
                $output->writeln('<info>Customer <comment>' . $email . '</comment> successfully created</info>');
            } else {
                $output->writeln('<error>Customer ' . $email . ' already exists</error>');
            }
        }
    }
}