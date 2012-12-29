<?php

namespace N98\Magento\Command\Customer;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDummyCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:create:dummy')
            ->addArgument('count', InputArgument::REQUIRED, 'Count')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website')
            ->setDescription('Creates a dummy customers.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $website = $this->getHelperSet()->get('parameter')->askWebsite($input, $output);

            for ($i = 0; $i < $input->getArgument('count'); $i++) {
                $customer = $this->getCustomerModel();

                $faker = \Faker\Factory::create($input->getArgument('locale'));

                $email = $faker->safeEmail;

                $customer->setWebsiteId($website->getId());
                $customer->loadByEmail($email);
                $password = $customer->generatePassword();

                if (!$customer->getId()) {
                    $customer->setWebsiteId($website->getId());
                    $customer->setEmail($email);
                    $customer->setFirstname($faker->firstName);
                    $customer->setLastname($faker->lastName);
                    $customer->setPassword($password);

                    $customer->save();
                    $customer->setConfirmation(null);
                    $customer->save();
                    $output->writeln('<info>Customer <comment>' . $email . '</comment> with password <comment>' . $password .  '</comment> successfully created</info>');
                } else {
                    $output->writeln('<error>Customer ' . $email . ' already exists</error>');
                }
            }

        }
    }
}