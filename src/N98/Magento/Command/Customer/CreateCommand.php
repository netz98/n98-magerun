<?php

namespace N98\Magento\Command\Customer;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
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
        if (!$this->initMagento()) {
            return;
        }

        $dialog = $this->getHelper('dialog');

        // Email
        $email = $this->getHelper('parameter')->askEmail($input, $output);

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $password = $dialog->askHiddenResponse($output, '<question>Password:</question>');
        }

        // Firstname
        if (($firstname = $input->getArgument('firstname')) == null) {
            $firstname = $dialog->ask($output, '<question>Firstname:</question>');
        }

        // Lastname
        if (($lastname = $input->getArgument('lastname')) == null) {
            $lastname = $dialog->ask($output, '<question>Lastname:</question>');
        }

        $website = $this->getHelper('parameter')->askWebsite($input, $output);

        // create new customer
        $customer = $this->getCustomerModel();
        $customer->setWebsiteId($website->getId());
        $customer->loadByEmail($email);

        $outputPlain = $input->getOption('format') === null;

        $table = array();
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
                $table[] = array(
                    $email, $password, $firstname, $lastname,
                );
            }
        } else {
            if ($outputPlain) {
                $output->writeln('<error>Customer ' . $email . ' already exists</error>');
            }
        }

        if (!$outputPlain) {
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('email', 'password', 'firstname', 'lastname'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }
}
