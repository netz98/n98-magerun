<?php

namespace N98\Magento\Command\Eav\Attribute\Create;

use Exception;
use Locale;
use Mage;
use Mage_Eav_Model_Entity_Attribute;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Create EAV attribute dummy command
 *
 * @package N98\Magento\Command\Eav\Attribute\Create
 */
class DummyCommand extends AbstractMagentoCommand
{
    private $supportedLocales = ['en_US', 'en_GB'];

    protected function configure()
    {
        $this
            ->setName('eav:attribute:create-dummy-values')->addArgument('locale', InputArgument::OPTIONAL, Locale::class)
            ->addArgument('attribute-id', InputArgument::OPTIONAL, 'Attribute ID to add values')
            ->addArgument('values-type', InputArgument::OPTIONAL, 'Types of Values to create (default int)')
            ->addArgument('values-number', InputArgument::OPTIONAL, 'Number of Values to create (default 1)')
            ->setDescription('Create a dummy values for dropdown attributes')
        ;
    }

    /**
     * {@inheritdoc}
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
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $output->writeln(
            '<warning>This only create sample attribute values, do not use on production environment</warning>'
        );

        // Ask for Arguments
        $argument = $this->askForArguments($input, $output);
        if (!in_array($input->getArgument('locale'), $this->supportedLocales)) {
            $output->writeln(
                sprintf(
                    "<warning>Locale '%s' not supported, switch to default locale 'us_US'.</warning>",
                    $input->getArgument('locale')
                )
            );
            $argument['locale'] = 'en_US';
        } else {
            $argument['locale'] = $input->getArgument('locale');
        }

        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute = Mage::getModel('eav/entity_attribute')->load($argument['attribute-id']);
        $dummyValues = new DummyValues();
        for ($i = 0; $i < $argument['values-number']; $i++) {
            $value = $dummyValues->createValue($argument['values-type'], $argument['locale']);
            if (!$this->attributeValueExists($attribute, $value)) {
                try {
                    $attribute->setData('option', ['value' => ['option' => [$value, $value]]]);
                    $attribute->save();
                } catch (Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
                $output->writeln("<comment>ATTRIBUTE VALUE: '" . $value . "' ADDED!</comment>\r");
            }
        }
        return 0;
    }

    /**
     * Ask for command arguments
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    private function askForArguments(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getQuestionHelper();
        $argument = [];

        // Attribute ID
        if (is_null($input->getArgument('attribute-id'))) {
            $attribute_code = Mage::getModel('eav/entity_attribute')
                ->getCollection()->addFieldToSelect('*')
                ->addFieldToFilter('entity_type_id', ['eq' => 4])
                ->addFieldToFilter('backend_type', ['in' => ['int']])
                ->setOrder('attribute_id', 'ASC')
            ;
            $attribute_codes = [];

            foreach ($attribute_code as $item) {
                $attribute_codes[$item['attribute_id']] = $item['attribute_id'] . '|' . $item['attribute_code'];
            }

            $question = new ChoiceQuestion('Please select Attribute ID', $attribute_codes);
            $question->setErrorMessage('Attribute ID "%s" is invalid.');
            $response = explode('|', $dialog->ask($input, $output, $question));
            $input->setArgument('attribute-id', $response[0]);
        }
        $output->writeln('<info>Attribute code selected: ' . $input->getArgument('attribute-id') . '</info>');
        $argument['attribute-id'] = (int) $input->getArgument('attribute-id');

        // Type of Values
        if (is_null($input->getArgument('values-type'))) {
            $valueTypes = DummyValues::getValueTypeList();
            $question = new ChoiceQuestion('Please select Attribute Value Type', $valueTypes, 'int');
            $question->setErrorMessage('Attribute Value Type "%s" is invalid.');
            $input->setArgument('values-type', $dialog->ask($input, $output, $question));
        }
        $output->writeln('<info>Attribute Value Type selected: ' . $input->getArgument('values-type') . '</info>');
        $argument['values-type'] = $input->getArgument('values-type');

        // Number of Values
        if (is_null($input->getArgument('values-number'))) {
            $question = new Question('Please enter the number of values to create (default 1): ', 1);
            $question->setValidator(function ($answer) {
                $answer = (int) ($answer);
                if (!is_int($answer) || $answer <= 0) {
                    throw new RuntimeException('Please enter an integer value or > 0');
                }

                return $answer;
            });
            $input->setArgument('values-number', $dialog->ask($input, $output, $question));
        }
        $output->writeln('<info>Number of values to create: ' . $input->getArgument('values-number') . '</info>');
        $argument['values-number'] = $input->getArgument('values-number');

        return $argument;
    }

    /**
     * Check if an option exist
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param string                          $arg_value
     *
     * @return bool
     */
    private function attributeValueExists(Mage_Eav_Model_Entity_Attribute $attribute, $arg_value)
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
