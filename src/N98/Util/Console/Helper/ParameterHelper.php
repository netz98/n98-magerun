<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Mapping\BlackholeMetadataFactory;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Helper to init some parameters
 */
class ParameterHelper extends AbstractHelper
{
    /**
     * @var
     */
    protected $validator = null;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'parameter';
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $argumentName
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function askStore(InputInterface $input, OutputInterface $output, $argumentName = 'store')
    {
        try {
            if ($input->getArgument($argumentName) === null) {
                throw new \Exception('No store given');
            }
            $store = \Mage::app()->getStore($input->getArgument($argumentName));
        } catch (\Exception $e) {
            $stores = array();
            $i = 0;
            foreach (\Mage::app()->getStores() as $store) {
                $stores[$i] = $store->getId();
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $store->getCode() . ' - ' . $store->getName() . PHP_EOL;
                $i++;
            }
            $question[] = '<question>Please select a store: </question>';

            $storeId = $this->getHelperSet()->get('dialog')->askAndValidate($output, $question, function($typeInput) use ($stores) {
                if (!isset($stores[$typeInput - 1])) {
                    throw new \InvalidArgumentException('Invalid store');
                }

                return $stores[$typeInput - 1];
            });

            $store = \Mage::app()->getStore($storeId);
        }

        return $store;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $argumentName
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function askWebsite(InputInterface $input, OutputInterface $output, $argumentName = 'website')
    {
        try {
            if ($input->getArgument($argumentName) === null) {
                throw new \Exception('No website given');
            }
            $website = \Mage::app()->getWebsite($input->getArgument($argumentName));
        } catch (\Exception $e) {
            $i = 0;
            $websites = array();
            foreach (\Mage::app()->getWebsites() as $website) {
                $websites[$i] = $website->getId();
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $website->getCode() . ' - ' . $website->getName() . PHP_EOL;
                $i++;
            }
            $question[] = '<question>Please select a website: </question>';

            $websiteId = $this->getHelperSet()->get('dialog')->askAndValidate($output, $question, function($typeInput) use ($websites) {
                if (!isset($websites[$typeInput - 1])) {
                    throw new \InvalidArgumentException('Invalid store');
                }

                return $websites[$typeInput - 1];
            });

            $website = \Mage::app()->getWebsite($websiteId);
        }

        return $website;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $argumentName
     * @return string
     */
    public function askEmail(InputInterface $input, OutputInterface $output, $argumentName = 'email')
    {
        $this->initValidator();
        $email = $input->getArgument($argumentName);
        $validator = $this->validator;
        $errors = $validator->validateValue($email, new Constraints\Email());
        if (count($errors) > 0) {
            $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            $question = '<question>Email: </question>';
            $email = $this->getHelperSet()->get('dialog')->askAndValidate(
                $output,
                $question,
                function($typeInput) use ($validator) {
                    $errors = $validator->validateValue($typeInput, new Constraints\Email());
                    if (count($errors) > 0) {
                        throw new \InvalidArgumentException($errors[0]->getMessage());
                    }

                    return $typeInput;
                }
            );
        }

        return $email;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $argumentName
     * @return string
     */
    public function askPassword(InputInterface $input, OutputInterface $output, $argumentName = 'password')
    {
        $this->initValidator();
        $password = $input->getArgument($argumentName);
        $validator = $this->validator;
        $constraints = new Constraints\Collection(
            array(
                'password' => array(
                    new Constraints\Regex(array('pattern' => '/^(?=.*\d)(?=.*[a-zA-Z])/', 'message' => 'Password must contain letters and at least one digit')),
                    new Constraints\MinLength(array('limit' => 6))
                )
            )
        );
        $errors = $validator->validateValue(array('password' => $password), $constraints);
        if (count($errors) > 0) {
            $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            $question = '<question>Password: </question>';
            $password = $this->getHelperSet()->get('dialog')->askAndValidate(
                $output,
                $question,
                function($typeInput) use ($validator, $constraints) {
                    $errors = $validator->validateValue(array('password' => $typeInput), $constraints);
                    if (count($errors) > 0) {
                        throw new \InvalidArgumentException($errors[0]->getMessage());
                    }

                    return $typeInput;
                }
            );
        }

        return $password;
    }

    protected function initValidator()
    {
        if ($this->validator == null) {
            $factory = new ConstraintValidatorFactory();
            $this->validator = new Validator(new BlackholeMetadataFactory(), $factory);
        }

        return $this->validator;
    }
}
