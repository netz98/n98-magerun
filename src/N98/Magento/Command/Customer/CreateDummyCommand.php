<?php

namespace N98\Magento\Command\Customer;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDummyCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $help = <<<HELP
Supported Locales:

- cs_CZ
- ru_RU
- bg_BG
- en_US
- it_IT
- sr_RS
- sr_Cyrl_RS
- sr_Latn_RS
- pl_PL
- en_GB
- de_DE
- sk_SK
- fr_FR
- es_AR
- de_AT
HELP;

        $this
            ->setName('customer:create:dummy')
            ->addArgument('count', InputArgument::REQUIRED, 'Count')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website')
            ->addOption(
                'with-addresses',
                null,
                InputOption::VALUE_NONE,
                'Create dummy billing/shipping addresses for each customers'
            )
            ->setDescription('Generate dummy customers. You can specify a count and a locale.')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setHelp($help)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $res = $this->getCustomerModel()->getResource();

        $faker = \Faker\Factory::create($input->getArgument('locale'));
        $faker->addProvider(new \N98\Util\Faker\Provider\Internet($faker));

        $website = $this->getHelper('parameter')->askWebsite($input, $output);

        $res->beginTransaction();
        $count = $input->getArgument('count');
        $outputPlain = $input->getOption('format') === null;

        $table = array();
        for ($i = 0; $i < $count; $i++) {
            $customer = $this->getCustomerModel();

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

                if ($input->hasOption('with-addresses')) {
                    $address = $this->createAddress($faker);
                    $customer->addAddress($address);
                }

                $customer->save();
                $customer->setConfirmation(null);
                $customer->save();

                if ($outputPlain) {
                    $output->writeln(
                        '<info>Customer <comment>' . $email . '</comment> with password <comment>' . $password .
                        '</comment> successfully created</info>'
                    );
                } else {
                    $table[] = array(
                        $email, $password, $customer->getFirstname(), $customer->getLastname(),
                    );
                }
            } else {
                if ($outputPlain) {
                    $output->writeln('<error>Customer ' . $email . ' already exists</error>');
                }
            }
            if ($i % 1000 == 0) {
                $res->commit();
                $res->beginTransaction();
            }
        }
        $res->commit();

        if (!$outputPlain) {
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(array('email', 'password', 'firstname', 'lastname'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }

    private function createAddress($faker)
    {
        $country = $this->getCountryCollection()
            ->addCountryCodeFilter($faker->countryCode, 'iso2')
            ->getFirstItem();

        $regions = $country->getRegions()->getData();
        $region = $regions[array_rand($regions)];

        $address = $this->getAddressModel();
        $address->setFirstname($faker->firstName);
        $address->setLastname($faker->lastName);
        $address->setCity($faker->city);
        $address->setCountryId($country->getId());
        if ($region) {
            $address->setRegionId($region['region_id']);
        }

        $address->setStreet($faker->streetAddress);
        $address->setPostcode($faker->postcode);
        $address->setTelephone($faker->phoneNumber);
        $address->setIsSubscribed($faker->boolean());

        $address->setIsDefaultShipping(true);
        $address->setIsDefaultBilling(true);

        return $address;
    }
}
