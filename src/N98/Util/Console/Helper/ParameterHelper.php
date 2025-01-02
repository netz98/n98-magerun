<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use Exception;
use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_App;
use Mage_Core_Model_Store;
use Mage_Core_Model_Store_Exception;
use Mage_Core_Model_Website;
use N98\Util\Validator\FakeMetadataFactory;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Helper to init some parameters
 *
 * @package N98\Util\Console\Helper
 */
class ParameterHelper extends AbstractHelper
{
    private ?ValidatorInterface $validator = null;

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName(): string
    {
        return 'parameter';
    }

    /**
     * @throws Mage_Core_Model_Store_Exception
     */
    public function askStore(
        InputInterface  $input,
        OutputInterface $output,
        string          $argumentName = 'store',
        bool            $withDefaultStore = false
    ): ?Mage_Core_Model_Store {
        /** @var Mage_Core_Model_App $storeManager */
        $storeManager = Mage::app();

        try {
            if ($input->getArgument($argumentName) === null) {
                throw new RuntimeException('No store given');
            }

            /** @var Mage_Core_Model_Store $store */
            $store = $storeManager->getStore($input->getArgument($argumentName));
        } catch (Exception $exception) {
            if (!$input->isInteractive()) {
                throw new RuntimeException(sprintf('Require %s parameter', $argumentName), $exception->getCode(), $exception);
            }

            $stores = [];
            $choices = [];

            foreach ($storeManager->getStores($withDefaultStore) as $store) {
                $stores[] = $store->getId();
                $choices[] = sprintf(
                    '%s - %s',
                    $store->getCode(),
                    $store->getName(),
                );
            }

            if (count($stores) > 1) {
                $validator = function ($typeInput) use ($stores, $exception) {
                    if (!isset($stores[$typeInput])) {
                        throw new InvalidArgumentException('Invalid store', $exception->getCode(), $exception);
                    }

                    return $stores[$typeInput];
                };

                /* @var QuestionHelper $dialog */
                $questionHelper = new QuestionHelper();
                $choiceQuestion = new ChoiceQuestion('<question>Please select a store:</question> ', $choices);
                $choiceQuestion->setValidator($validator);

                $storeId = $questionHelper->ask($input, $output, $choiceQuestion);
            } else {
                // only one store view available -> take it
                $storeId = $stores[0];
            }

            $store = $storeManager->getStore($storeId);
        }

        return $store;
    }

    /**
     * @throws InvalidArgumentException|Mage_Core_Exception
     */
    public function askWebsite(InputInterface $input, OutputInterface $output, string $argumentName = 'website'): ?Mage_Core_Model_Website
    {
        /* @var Mage_Core_Model_App $storeManager */
        $storeManager = Mage::app();

        $website = null;
        $argumentValue = $input->getArgument($argumentName);
        $hasArgument = $argumentValue !== null;

        if ($hasArgument) {
            try {
                /* @var Mage_Core_Model_Website $website */
                $website = $storeManager->getWebsite($argumentValue);
                return $website;
            } catch (Exception $exception) {
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

        $questionHelper = new QuestionHelper();
        $choiceQuestion = new ChoiceQuestion('<question>Please select a website:</question> ', $choices);
        $choiceQuestion->setValidator($validator);

        $websiteId = $questionHelper->ask($input, $output, $choiceQuestion);

        return $storeManager->getWebsite($websiteId);
    }

    /**
     * @see askWebsite
     * @return array websites (integers with website IDs, 0-indexed) and question array (strings)
     */
    private function websitesQuestion(Mage_Core_Model_App $mageCoreModelApp): array
    {
        $websites = [];
        $question = [];
        foreach ($mageCoreModelApp->getWebsites() as $website) {
            $websites[] = $website->getId();
            $question[] = sprintf('%s - %s', $website->getCode(), $website->getName());
        }

        return [$websites, $question];
    }

    public function askEmail(InputInterface $input, OutputInterface $output, string $argumentName = 'email'): string
    {
        $collection = new Collection(
            ['email' => [new NotBlank(), new Email()]],
        );

        return $this->validateArgument($input, $output, $argumentName, $input->getArgument($argumentName), $collection);
    }

    /**
     * @param bool $needDigits [optional]
     */
    public function askPassword(
        InputInterface  $input,
        OutputInterface $output,
        string          $argumentName = 'password',
        bool            $needDigits = true
    ): string {
        $validators = [];

        if ($needDigits) {
            $regex = ['pattern' => '/^(?=.*\d)(?=.*[a-zA-Z])/', 'message' => 'Password must contain letters and at least one digit'];
            $validators[] = new Regex($regex);
        }

        $validators[] = new Length(['min' => 6]);

        $collection = new Collection(
            ['password' => $validators],
        );

        return $this->validateArgument($input, $output, $argumentName, $input->getArgument($argumentName), $collection);
    }

    /**
     * @return mixed
     */
    private function askAndValidate(InputInterface $input, OutputInterface $output, string $question, callable $callback)
    {
        $questionHelper = new QuestionHelper();
        $questionObj = new Question($question);
        $questionObj->setValidator($callback);

        return $questionHelper->ask($input, $output, $questionObj);
    }

    private function validateArgument(
        InputInterface  $input,
        OutputInterface $output,
        string          $name,
        string          $value,
        Collection      $collection
    ): string {
        $this->initValidator();

        if (strlen($value) !== 0) {
            $errors = $this->validateValue($name, $value, $collection);
            if ($errors->count() > 0) {
                $output->writeln('<error>' . $errors[0]->getMessage() . '</error>');
            } else {
                return $value;
            }
        }

        $question = '<question>' . ucfirst($name) . ':</question> ';

        return $this->askAndValidate(
            $input,
            $output,
            $question,
            function ($inputValue) use ($collection, $name) {
                $errors = $this->validateValue($name, $inputValue, $collection);
                if ($errors->count() > 0) {
                    throw new InvalidArgumentException((string) $errors[0]->getMessage());
                }

                return $inputValue;
            },
        );
    }

    /**
     * @return ConstraintViolationInterface[]|ConstraintViolationListInterface
     */
    private function validateValue(string $name, string $value, Collection $collection)
    {
        $validator = $this->validator;
        /** @var ConstraintViolationListInterface|ConstraintViolationInterface[] $constraintViolationList */
        $constraintViolationList = $validator->validate([$name => $value], $collection);

        return $constraintViolationList;
    }

    protected function initValidator(): ValidatorInterface
    {
        if (is_null($this->validator)) {
            $this->validator = Validation::createValidatorBuilder()
                ->setConstraintValidatorFactory(new ConstraintValidatorFactory())
                ->setMetadataFactory(new FakeMetadataFactory())
                ->getValidator();
        }

        return $this->validator;
    }
}
