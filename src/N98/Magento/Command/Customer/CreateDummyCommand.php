<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Faker\Factory;
use Faker\Generator;
use Mage_Customer_Model_Address;
use N98\Util\Faker\Provider\Internet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create customer dummy command
 *
 * @package N98\Magento\Command\Customer
 */
class CreateDummyCommand extends AbstractCustomerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('customer:create:dummy')
            ->addArgument('count', InputArgument::REQUIRED, 'Count')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website')
            ->addOption(
                'with-addresses',
                null,
                InputOption::VALUE_NONE,
                'Create dummy billing/shipping addresses for each customers',
            )
            ->setDescription('Generate dummy customers. You can specify a count and a locale.')
            ->addFormatOption()
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $res = $this->getCustomerModel()->getResource();

        $generator = Factory::create($input->getArgument('locale'));
        $generator->addProvider(new Internet($generator));

        $parameterHelper = $this->getParameterHelper();

        $website = $parameterHelper->askWebsite($input, $output);

        $res->beginTransaction();
        $count = $input->getArgument('count');
        $outputPlain = $input->getOption('format') === null;

        $table = [];
        for ($i = 0; $i < $count; ++$i) {
            $customer = $this->getCustomerModel();

            $email = $generator->safeEmail;

            $customer->setWebsiteId((int) $website->getId());
            $customer->loadByEmail($email);
            $password = $customer->generatePassword();

            if (!$customer->getId()) {
                $customer->setWebsiteId((int) $website->getId());
                $customer->setEmail($email);
                $customer->setFirstname($generator->firstName); # @phpstan-ignore method.notFound (missing in current OpenMage)
                $customer->setLastname($generator->lastName);   # @phpstan-ignore method.notFound (missing in current OpenMage)
                $customer->setPassword($password);
                if ($input->hasOption('with-addresses')) {
                    $address = $this->createAddress($generator);
                    $customer->addAddress($address);
                }

                $customer->save();
                $customer->setConfirmation(null);
                $customer->save();
                if ($outputPlain) {
                    $output->writeln(
                        '<info>Customer <comment>' . $email . '</comment> with password <comment>' . $password .
                        '</comment> successfully created</info>',
                    );
                } else {
                    $table[] = [$email, $password, $customer->getFirstname(), $customer->getLastname()];
                }
            } elseif ($outputPlain) {
                $output->writeln('<error>Customer ' . $email . ' already exists</error>');
            }

            if ($i % 1000 == 0) {
                $res->commit();
                $res->beginTransaction();
            }
        }

        $res->commit();

        if (!$outputPlain) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['email', 'password', 'firstname', 'lastname'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        }

        return Command::SUCCESS;
    }

    private function createAddress(Generator $faker): Mage_Customer_Model_Address
    {
        $country = $this->getCountryCollection()
            ->addCountryCodeFilter($faker->countryCode, 'iso2')
            ->getFirstItem();

        $regions = $country->getRegions()->getData(); # @phpstan-ignore method.notFound (missing in current OpenMage)
        $region = $regions ? $regions[array_rand($regions)] : null;

        $mageCustomerModelAddress = $this->getAddressModel();
        $mageCustomerModelAddress->setFirstname($faker->firstName);
        $mageCustomerModelAddress->setLastname($faker->lastName);
        $mageCustomerModelAddress->setCity($faker->city);
        $mageCustomerModelAddress->setCountryId($country->getId());
        if ($region) {
            $mageCustomerModelAddress->setRegionId($region['region_id']);
        }

        $mageCustomerModelAddress->setStreet($faker->streetAddress);
        $mageCustomerModelAddress->setPostcode($faker->postcode);
        $mageCustomerModelAddress->setTelephone($faker->phoneNumber);
        $mageCustomerModelAddress->setIsSubscribed($faker->boolean()); # @phpstan-ignore method.notFound (missing in current OpenMage)

        $mageCustomerModelAddress->setIsDefaultShipping(true);
        $mageCustomerModelAddress->setIsDefaultBilling(true);

        return $mageCustomerModelAddress;
    }
}
