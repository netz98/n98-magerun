<?php

namespace N98\Util\Console\Helper;

use Exception;
use InvalidArgumentException;
use Mage_Core_Model_Website;
use N98\Util\Validator\FakeMetadataFactory;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator;

/**
 * Helper to init some parameters
 */
class ParameterHelper extends AbstractHelper
{
    /**
     * @var Validator
     */
    private $validator;

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
     * @param bool $withDefaultStore [optional]
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function askStore(
        InputInterface $input,
        OutputInterface $output,
        $argumentName = 'store',
        $withDefaultStore = false
    ) {
        /* @var $storeManager \Mage_Core_Model_App */
        $storeManager = \Mage::app();

        try {
            if ($input->getArgument($argumentName) === null) {
                throw new RuntimeException('No store given');
            }
            /** @var $store \Mage_Core_Model_Store */
            $store = $storeManager->getStore($input->getArgument($argumentName));
        } catch (Exception $e) {
            if (!$input->isInteractive()) {
                throw new RuntimeException(sprintf('Require %s parameter', $argumentName));
            }

            $stores = array();
            $question = array();
            $i = 0;

            foreach ($storeManager->getStores($withDefaultStore) as $store) {
                $stores[$i] = $store->getId();
                $question[] = sprintf(
                    '<comment>[%d]</comment> %s - %s' . PHP_EOL,
                    ++$i,
                    $store->getCode(),
                    $store->getName()
                );
            }

            if (count($stores) > 1) {
                $question[] = '<question>Please select a store: </question>';
                $storeId = $this->askAndValidate(
                    $output,
                    $question,
                    function ($typeInput) use ($stores) {
                        if (!isset($stores[$typeInput - 1])) {
                            throw new InvalidArgumentException('Invalid store');
                        }

                        return $stores[$typeInput - 1];
                    }
                );
            } else {
                // only one store view available -> take it
                $storeId = $stores[0];
            }

            $store = $storeManager->getStore($storeId);
        }

        return $store;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function askWebsite(InputInterface $input, OutputInterface $output, $argumentName = 'website')
    {
        /* @var $storeManager \Mage_Core_Model_App */
        $storeManager = \Mage::app();

        $website = null;
        $argumentValue = $input->getArgument($argumentName);
        $hasArgument = $argumentValue !== null;

        if ($hasArgument) {
            try {
                /* @var $website Mage_Core_Model_Website */
                $website = $storeManager->getWebsite($argumentValue);
                return $website;
            } catch (Exception $e) {
                // catch all exceptions
            }
        }

        list($websites, $question) = $this->websitesQuestion($storeManager);
        if (count($websites) === 1) {
            return $storeManager->getWebsite($websites[0]);
        }

        $question[] = '<question>Please select a website: </question>';
        $callback = function ($typeInput) use ($websites) {
            if (!isset($websites[$typeInput - 1])) {
                throw new InvalidArgumentException('Invalid website');
            }

            return $websites[$typeInput - 1];
        };

        $websiteId = $this->askAndValidate($output, $question, $callback);
        $website = $storeManager->getWebsite($websiteId);

        return $website;
    }

    /**
     * @see askWebsite
     * @return array websites (integers with website IDs, 0-indexed) and question array (strings)
     */
    private function websitesQuestion($storeManager)
    {
        $i = 0;
        $websites = array();
        $question = array();
        /* @var $website Mage_Core_Model_Website */
        foreach ($storeManager->getWebsites() as $website) {
            $position = $i++;
            $websites[$position] = $website->getId();
            $question[$position] = sprintf(
                '<comment>[%d]</comment> %s' . PHP_EOL,
                $position + 1,
                sprintf('%s - %s', $website->getCode(), $website->getName())
            );
        }

        return array($websites, $question);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     *
     * @return string
     */
    public function askEmail(InputInterface $input, OutputInterface $output, $argumentName = 'email')
    {
        $constraints = new Constraints\Collection(
            array(
                'email' => array(
                    new Constraints\NotBlank(),
                    new Constraints\Email(),
                ),
            )
        );

        return $this->validateArgument($output, $argumentName, $input->getArgument($argumentName), $constraints);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $argumentName
     *
     * @param bool $needDigits [optional]
     * @return string
     */
    public function askPassword(
        InputInterface $input,
        OutputInterface $output,
        $argumentName = 'password',
        $needDigits = true
    ) {
        $validators = array();

        if ($needDigits) {
            $regex = array(
                'pattern' => '/^(?=.*\d)(?=.*[a-zA-Z])/',
                'message' => 'Password must contain letters and at least one digit',
            );
            $validators[] = new Constraints\Regex($regex);
        }

        $validators[] = new Constraints\Length(array('min' => 6));

        $constraints = new Constraints\Collection(
            array(
                'password' => $validators,
            )
        );

        return $this->validateArgument($output, $argumentName, $input->getArgument($argumentName), $constraints);
    }

    /**
     * @param OutputInterface $output
     * @param string|array $question
     * @param callable $callback
     *
     * @return string
     */
    private function askAndValidate(OutputInterface $output, $question, $callback)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        return $dialog->askAndValidate($output, $question, $callback);
    }

    /**
     * @param OutputInterface $output
     * @param string $name
     * @param string $value
     * @param Constraints\Collection $constraints The constraint(s) to validate against.
     *
     * @return string
     */
    private function validateArgument(OutputInterface $output, $name, $value, $constraints)
    {
        if (strlen($value)) {
            $errors = $this->validateValue($name, $value, $constraints);
            if ($errors->count() > 0) {
                $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            } else {
                return $value;
            }
        }

        $question = '<question>' . ucfirst($name) . ': </question>';

        $value = $this->askAndValidate(
            $output,
            $question,
            function ($inputValue) use ($constraints, $name) {
                $errors = $this->validateValue($name, $inputValue, $constraints);
                if ($errors->count() > 0) {
                    throw new InvalidArgumentException($errors[0]->getMessage());
                }

                return $inputValue;
            }
        );

        return $value;
    }

    /**
     * @param string $name
     * @param string $value
     * @param Constraints\Collection $constraints The constraint(s) to validate against.
     *
     * @return \Symfony\Component\Validator\ConstraintViolationInterface[]|ConstraintViolationListInterface
     */
    private function validateValue($name, $value, $constraints)
    {
        $validator = $this->getValidator();
        /** @var ConstraintViolationListInterface|ConstraintViolationInterface[] $errors */
        $errors = $validator->validateValue(array($name => $value), $constraints);

        return $errors;
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return $this->validator ?: $this->validator = $this->createValidator();
    }

    /**
     * @return Validator
     */
    private function createValidator()
    {
        $factory = new ConstraintValidatorFactory();
        $validator = new Validator(new FakeMetadataFactory(), $factory, new Translator('en'));

        return $validator;
    }
}
