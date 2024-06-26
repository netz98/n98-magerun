<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Faker\Factory;
use Faker\Generator;
use Mage_Core_Model_Website;
use Mage_Customer_Model_Address;
use N98\Util\Faker\Provider\Internet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create dummy customer command
 *
 * @package N98\Magento\Command\Customer
 */
class CreateDummyCommand extends AbstractCustomerCommand
{
    public const COMMAND_ARGUMENT_COUNT = 'count';

    public const COMMAND_ARGUMENT_LOCALE = 'locale';

    public const COMMAND_OPTION_WITH_ADDRESSES = 'with-addresses';

    /**
     * @var string
     */
    protected static $defaultName = 'customer:create:dummy';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Generate dummy customers. You can specify a count and a locale.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_COUNT,
                InputArgument::REQUIRED,
                'Count'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_LOCALE,
                InputArgument::REQUIRED,
                'Locale'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_WEBSITE,
                InputArgument::OPTIONAL,
                'Website'
            )
            ->addOption(
                self::COMMAND_OPTION_WITH_ADDRESSES,
                null,
                InputOption::VALUE_NONE,
                'Create dummy billing/shipping addresses for each customers'
            )
            ->addFormatOption()
        ;
    }

    /**
     * @return string
     */
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $res = $this->getCustomerModel()->getResource();

        /** @var string $locale */
        $locale = $input->getArgument(self::COMMAND_ARGUMENT_LOCALE);
        $faker = Factory::create($locale);
        $faker->addProvider(new Internet($faker));

        $parameterHelper = $this->getParameterHelper();

        /** @var Mage_Core_Model_Website $website */
        $website = $parameterHelper->askWebsite($input, $output);

        $res->beginTransaction();
        $count = $input->getArgument(self::COMMAND_ARGUMENT_COUNT);
        $outputPlain = $input->getOption(self::COMMAND_OPTION_FORMAT) === null;

        $table = [];
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

                if ($input->hasOption(self::COMMAND_OPTION_WITH_ADDRESSES)) {
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
                    $table[] = [$email, $password, $customer->getFirstname(), $customer->getLastname()];
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
            /** @var string $format */
            $format = $input->getOption(self::COMMAND_OPTION_FORMAT);
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['email', 'password', 'firstname', 'lastname'])
                ->renderByFormat($output, $table, $format);
        }

        return Command::SUCCESS;
    }

    /**
     * @param Generator $faker
     * @return Mage_Customer_Model_Address
     */
    private function createAddress($faker): Mage_Customer_Model_Address
    {
        $country = $this->getCountryCollection()
            ->addCountryCodeFilter($faker->countryCode, 'iso2')
            ->getFirstItem();

        $regions = $country->getRegions()->getData();
        $region = $regions ? $regions[array_rand($regions)] : null;

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
