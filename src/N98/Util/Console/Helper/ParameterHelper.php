<?php

namespace N98\Util\Console\Helper;

use Exception;
use InvalidArgumentException;
use JsonSchema\Validator;
use Mage;
use Mage_Core_Model_Website;
use N98\Util\Validator\FakeMetadataFactory;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Helper to init some parameters
 *
 * @package N98\Util\Console\Helper
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
        /* @var \Mage_Core_Model_App $storeManager */
        $storeManager = Mage::app();

        try {
            if ($input->getArgument($argumentName) === null) {
                throw new RuntimeException('No store given');
            }
            /** @var \Mage_Core_Model_Store $store */
            $store = $storeManager->getStore($input->getArgument($argumentName));
        } catch (Exception $e) {
            if (!$input->isInteractive()) {
                throw new RuntimeException(sprintf('Require %s parameter', $argumentName));
            }

            $stores = [];
            $choices = [];

            foreach ($storeManager->getStores($withDefaultStore) as $store) {
                $stores[] = $store->getId();
                $choices[] = sprintf(
                    '%s - %s',
                    $store->getCode(),
                    $store->getName()
                );
            }

            if (count($stores) > 1) {
                $validator = function ($typeInput) use ($stores) {
                    if (!isset($stores[$typeInput])) {
                        throw new InvalidArgumentException('Invalid store');
                    }

                    return $stores[$typeInput];
                };

                /* @var QuestionHelper $dialog */
                $dialog = new QuestionHelper();
                $question = new ChoiceQuestion('<question>Please select a store:</question> ', $choices);
                $question->setValidator($validator);

                $storeId = $dialog->ask($input, $output, $question);
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
        /* @var \Mage_Core_Model_App $storeManager */
        $storeManager = Mage::app();

        $website = null;
        $argumentValue = $input->getArgument($argumentName);
        $hasArgument = $argumentValue !== null;

        if ($hasArgument) {
            try {
                /* @var Mage_Core_Model_Website $website */
                $website = $storeManager->getWebsite($argumentValue);
                return $website;
            } catch (Exception $e) {
                // catch all exceptions
            }
        }

        [$websites, $choices] = $this->websitesQuestion($storeManager);
        if ((is_countable($websites) ? count($websites) : 0) === 1) {
            return $storeManager->getWebsite($websites[0]);
        }

        $validator = function ($typeInput) use ($websites) {
            if (!isset($websites[$typeInput])) {
                throw new InvalidArgumentException('Invalid website');
            }

            return $websites[$typeInput];
        };

        /* @var QuestionHelper $dialog */
        $dialog = new QuestionHelper();
        $question = new ChoiceQuestion('<question>Please select a website:</question> ', $choices);
        $question->setValidator($validator);

        $websiteId = $dialog->ask($input, $output, $question);
        $website = $storeManager->getWebsite($websiteId);

        return $website;
    }

    /**
     * @see askWebsite
     * @return array websites (integers with website IDs, 0-indexed) and question array (strings)
     */
    private function websitesQuestion($storeManager)
    {
        $websites = [];
        $question = [];
        /* @var Mage_Core_Model_Website $website */
        foreach ($storeManager->getWebsites() as $website) {
            $websites[] = $website->getId();
            $question[] = sprintf('%s - %s', $website->getCode(), $website->getName());
        }

        return [$websites, $question];
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
        $constraints = new Collection(
            ['email' => [new NotBlank(), new Email()]]
        );

        return $this->validateArgument($input, $output, $argumentName, $input->getArgument($argumentName), $constraints);
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
        $validators = [];

        if ($needDigits) {
            $regex = ['pattern' => '/^(?=.*\d)(?=.*[a-zA-Z])/', 'message' => 'Password must contain letters and at least one digit'];
            $validators[] = new Regex($regex);
        }

        $validators[] = new Length(['min' => 6]);

        $constraints = new Collection(
            ['password' => $validators]
        );

        return $this->validateArgument($input, $output, $argumentName, $input->getArgument($argumentName), $constraints);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param OutputInterface $output
     * @param string|array $question
     * @param callable $callback
     *
     * @return mixed
     */
    private function askAndValidate(InputInterface $input, OutputInterface $output, $question, $callback)
    {
        $dialog = new QuestionHelper();
        $questionObj = new Question($question);
        $questionObj->setValidator($callback);

        return $dialog->ask($input, $output, $questionObj);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @param string $value
     * @param Constraints\Collection $constraints The constraint(s) to validate against.
     *
     * @return string
     */
    private function validateArgument(InputInterface  $input, OutputInterface $output, $name, $value, $constraints)
    {
        $this->initValidator();

        if (strlen($value)) {
            $errors = $this->validateValue($name, $value, $constraints);
            if ($errors->count() > 0) {
                $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            } else {
                return $value;
            }
        }

        $question = '<question>' . ucfirst($name) . ':</question> ';

        $value = $this->askAndValidate(
            $input,
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
     * @return ConstraintViolationInterface[]|ConstraintViolationListInterface
     */
    private function validateValue($name, $value, $constraints)
    {
        $validator = $this->getValidator();
        /** @var ConstraintViolationListInterface|ConstraintViolationInterface[] $errors */
        $errors = $validator->validate([$name => $value], $constraints);

        return $errors;
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return $this->validator;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected function initValidator()
    {
        if (null === $this->validator) {
            $this->validator = \Symfony\Component\Validator\Validation::createValidatorBuilder()
                ->setConstraintValidatorFactory(new ConstraintValidatorFactory())
                ->setMetadataFactory(new FakeMetadataFactory())
                ->getValidator();
        }

        return $this->validator;
    }
}
