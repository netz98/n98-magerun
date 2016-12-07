<?php

namespace N98\Magento\Command\Customer;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePasswordCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:change-password')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website of the customer')
            ->setDescription('Changes the password of a customer.')
        ;

        $help = <<<HELP
- Website parameter must only be given if more than one websites are available.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        /* @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');
        $email = $this->getHelper('parameter')->askEmail($input, $output);

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $password = $dialog->askHiddenResponse($output, '<question>Password:</question>');
        }

        $website = $this->getHelper('parameter')->askWebsite($input, $output);

        $customer = $this->getCustomerModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');
            return;
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
    }
}
