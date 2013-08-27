<?php

namespace N98\Util\Console\Helper;

use N98\Util\Validator\FakeMetadataFactory;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Helper to init some parameters
 */
class ParameterHelper extends AbstractHelper
{
    /**
     * @var Validator
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     * @param bool $withDefaultStore
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function askStore(InputInterface $input, OutputInterface $output, $argumentName = 'store', $withDefaultStore = false)
    {
        try {
            if ($input->getArgument($argumentName) === null) {
                throw new \Exception('No store given');
            }
            $store = \Mage::app()->getStore($input->getArgument($argumentName));
        } catch (\Exception $e) {
            $stores = array();
            $i = 0;
            /** @var \Mage_Core_Model_Store $store */
            foreach (\Mage::app()->getStores($withDefaultStore) as $store) {
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
     * @param InputInterface $input
     * @param OutputInterface $output
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
            /** @var \Mage_Core_Model_Website $website */
            foreach (\Mage::app()->getWebsites() as $website) {
                $websites[$i] = $website->getId();
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $website->getCode() . ' - ' . $website->getName() . PHP_EOL;
                $i++;
            }
            if (count($websites) == 1) {
                return \Mage::app()->getWebsite($websites[0]);
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     * @return string
     */
    public function askEmail(InputInterface $input, OutputInterface $output, $argumentName = 'email')
    {
        $constraints = new Constraints\Collection(
            array(
                'email' => array(
                    new Constraints\NotBlank(),
                    new Constraints\Email()
                )
            )
        );

        return $this->_validateArgument($output, $argumentName, $input->getArgument($argumentName), $constraints);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     * @param bool $needDigits
     * @return string
     */
    public function askPassword(InputInterface $input, OutputInterface $output, $argumentName = 'password',
        $needDigits = true
    )
    {
        $validators = array();

        if ($needDigits) {
            $regex = array(
                'pattern' => '/^(?=.*\d)(?=.*[a-zA-Z])/',
                'message' => 'Password must contain letters and at least one digit'
            );
            $validators[] = new Constraints\Regex($regex);
        }

        $validators[] = new Constraints\Length(array('min' => 6));

        $constraints = new Constraints\Collection(
            array(
                'password' => $validators
            )
        );

        return $this->_validateArgument($output, $argumentName, $input->getArgument($argumentName), $constraints);
    }

    /**
     * @param OutputInterface $output
     * @param $name
     * @param string $value
     * @param $constraints
     * @return mixed
     */
    protected function _validateArgument(OutputInterface $output, $name, $value, $constraints)
    {
        $this->initValidator();
        $validator = $this->validator;

        $errors = array();
        if (!empty($value)) {
            /** @var ConstraintViolation[] $errors */
            $errors = $validator->validateValue(array($name => $value), $constraints);
            if (count($errors) > 0) {
                $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            }
        }

        if (count($errors) > 0 || empty($value)) {
            $question = '<question>' . ucfirst($name) . ': </question>';
            $value = $this->getHelperSet()->get('dialog')->askAndValidate(
                $output,
                $question,
                function ($typeInput) use ($validator, $constraints, $name) {
                    /** @var ConstraintViolation[] $errors */
                    $errors = $validator->validateValue(array($name => $typeInput), $constraints);
                    if (count($errors) > 0) {
                        throw new \InvalidArgumentException($errors[0]->getMessage());
                    }

                    return $typeInput;
                }
            );
            return $value;
        }
        return $value;
    }

    /**
     * Return current validator instance
     *
     * @return Validator
     */
    protected function initValidator()
    {
        if ($this->validator == null) {
            $factory = new ConstraintValidatorFactory();
            $this->validator = new Validator(new FakeMetadataFactory(), $factory, new Translator('en'));
        }

        return $this->validator;
    }
}
