<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute\Create;

use Exception;
use Locale;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Entity_Attribute;
use Mage_Eav_Model_Resource_Entity_Attribute_Collection;
use N98\Magento\Command\AbstractCommand;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * EAV create attribute dummy command
 *
 * @package N98\Magento\Command\Eav\Attribute
 */
class DummyCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_LOCALE = 'locale';

    public const COMMAND_ARGUMENT_ATTRIBUTE_ID = 'attribute-id';

    public const COMMAND_ARGUMENT_VALUES_TYPE = 'values-type';

    public const COMMAND_ARGUMENT_VALUES_NUMBER = 'values-number';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'eav:attribute:create-dummy-values';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Create a dummy values for dropdown attributes.';

    /**
     * @var string[]
     */
    private array $supportedLocales = ['en_US', 'en_GB'];

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_LOCALE,
                InputArgument::OPTIONAL,
                Locale::class
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ATTRIBUTE_ID,
                InputArgument::OPTIONAL,
                'Attribute ID to add values'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_VALUES_TYPE,
                InputArgument::OPTIONAL,
                'Types of Values to create (default int)'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_VALUES_NUMBER,
                InputArgument::OPTIONAL,
                'Number of Values to create (default 1)'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Supported Locales:

- en_US
- en_GB
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $output->writeln(
            "<warning>This only create sample attribute values, do not use on production environment</warning>"
        );

        // Ask for Arguments
        $argument = $this->askForArguments($input, $output);

        /** @var string $locale */
        $locale = $input->getArgument(self::COMMAND_ARGUMENT_LOCALE);
        if (!in_array($locale, $this->supportedLocales)) {
            $output->writeln(
                sprintf(
                    "<warning>Locale '%s' not supported, switch to default locale 'us_US'.</warning>",
                    $locale
                )
            );
            $argument[self::COMMAND_ARGUMENT_LOCALE] = 'en_US';
        } else {
            $argument[self::COMMAND_ARGUMENT_LOCALE] = $locale;
        }

        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute = Mage::getModel('eav/entity_attribute')->load($argument['attribute-id']);
        $dummyValues = new DummyValues();
        for ($i = 0; $i < $argument[self::COMMAND_ARGUMENT_VALUES_NUMBER]; $i++) {
            $value = $dummyValues->createValue(
                $argument[self::COMMAND_ARGUMENT_VALUES_TYPE],
                $argument[self::COMMAND_ARGUMENT_LOCALE]
            );

            if (!$this->attributeValueExists($attribute, $value)) {
                try {
                    $attribute->setData('option', ['value' => ['option' => [$value, $value]]]);
                    $attribute->save();
                } catch (Exception $e) {
                    $output->writeln("<error>" . $e->getMessage() . "</error>");
                }
                $output->writeln("<comment>ATTRIBUTE VALUE: '" . $value . "' ADDED!</comment>\r");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Ask for command arguments
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array<string, string>
     * @throws Mage_Core_Exception
     */
    private function askForArguments(InputInterface $input, OutputInterface $output): array
    {
        $dialog = $this->getQuestionHelper();
        $argument = [];

        // Attribute ID
        if (is_null($input->getArgument(self::COMMAND_ARGUMENT_ATTRIBUTE_ID))) {
            /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributeCodeCollection */
            $attributeCodeCollection = Mage::getModel('eav/entity_attribute')->getCollection();
            $attributeCodeCollection
                ->addFieldToSelect('*')
                ->addFieldToFilter('entity_type_id', ['eq' => 4])
                ->addFieldToFilter('backend_type', ['in' => ['int']])
                ->setOrder('attribute_id', 'ASC')
            ;
            $attribute_codes = [];

            /** @var Mage_Eav_Model_Entity_Attribute $item */
            foreach ($attributeCodeCollection as $item) {
                $attribute_codes[$item['attribute_id']] = $item['attribute_id'] . "|" . $item['attribute_code'];
            }

            $question = new ChoiceQuestion('Please select Attribute ID', $attribute_codes);
            $question->setErrorMessage('Attribute ID "%s" is invalid.');

            /** @var string $answer */
            $answer = $dialog->ask($input, $output, $question);
            $response = explode('|', $answer);
            $input->setArgument(self::COMMAND_ARGUMENT_ATTRIBUTE_ID, $response[0]);
        }

        /** @var string $attributeId */
        $attributeId = $input->getArgument(self::COMMAND_ARGUMENT_ATTRIBUTE_ID);
        $output->writeln(sprintf(
            '<info>Attribute code selected: %s</info>',
            $attributeId
        ));
        $argument[self::COMMAND_ARGUMENT_ATTRIBUTE_ID] = $attributeId;

        // Type of Values
        if (is_null($input->getArgument(self::COMMAND_ARGUMENT_VALUES_TYPE))) {
            $valueTypes = DummyValues::getValueTypeList();
            $question = new ChoiceQuestion('Please select Attribute Value Type', $valueTypes, 'int');
            $question->setErrorMessage('Attribute Value Type "%s" is invalid.');
            $input->setArgument(self::COMMAND_ARGUMENT_VALUES_TYPE, $dialog->ask($input, $output, $question));
        }

        /** @var string $valueType */
        $valueType = $input->getArgument(self::COMMAND_ARGUMENT_VALUES_TYPE);
        $output->writeln(sprintf(
            '<info>Attribute Value Type selected: %s</info>',
            $valueType
        ));
        $argument[self::COMMAND_ARGUMENT_VALUES_TYPE] = $valueType;

        // Number of Values
        if (is_null($input->getArgument(self::COMMAND_ARGUMENT_VALUES_NUMBER))) {
            $question = new Question("Please enter the number of values to create (default 1): ", 1);
            $question->setValidator(function ($answer) {
                $answer = (int) ($answer);
                if ($answer <= 0) {
                    throw new RuntimeException('Please enter an integer value or > 0');
                }

                return $answer;
            });
            $input->setArgument(self::COMMAND_ARGUMENT_VALUES_NUMBER, $dialog->ask($input, $output, $question));
        }

        /** @var string $valueNumber */
        $valueNumber = $input->getArgument(self::COMMAND_ARGUMENT_VALUES_NUMBER);
        $output->writeln(sprintf(
            '<info>Number of values to create: %s</info>',
            $valueNumber
        ));
        $argument[self::COMMAND_ARGUMENT_VALUES_NUMBER] = $valueNumber;

        return $argument;
    }

    /**
     * Check if an option exist
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param string|int $arg_value
     * @return bool
     */
    private function attributeValueExists(Mage_Eav_Model_Entity_Attribute $attribute, $arg_value): bool
    {
        $options = Mage::getModel('eav/entity_attribute_source_table');
        $options->setAttribute($attribute);
        $options = $options->getAllOptions(false);

        foreach ($options as $option) {
            if ($option['label'] === $arg_value) {
                return true;
            }
        }

        return false;
    }
}
