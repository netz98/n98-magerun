<?php

declare(strict_types=1);

namespace N98\Magento\Command\Category\Create;

use Mage;
use Mage_Catalog_Model_Category;
use Mage_Catalog_Model_Resource_Category_Collection;
use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Create category dummy command
 *
 * @package N98\Magento\Command\Category\Create
 */
class DummyCommand extends AbstractMagentoCommand
{
    public const DEFAULT_CATEGORY_NAME = 'My Awesome Category';

    public const DEFAULT_CATEGORY_STATUS = 1;

    // enabled
    public const DEFAULT_CATEGORY_ANCHOR = 1;

    // enabled
    public const DEFAULT_STORE_ID = 1; // Default Store ID

    protected function configure(): void
    {
        $this
            ->setName('category:create:dummy')
            ->addArgument('store-id', InputArgument::OPTIONAL, 'Id of Store to create categories (default: 1)')
            ->addArgument('category-number', InputArgument::OPTIONAL, 'Number of categories to create (default: 1)')
            ->addArgument(
                'children-categories-number',
                InputArgument::OPTIONAL,
                "Number of children for each category created (default: 0 - use '-1' for random from 0 to 5)",
            )
            ->addArgument(
                'category-name-prefix',
                InputArgument::OPTIONAL,
                "Category Name Prefix (default: 'My Awesome Category')",
            )
            ->setDescription('Create a dummy category');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        $this->initMagento();

        $output->writeln('<warning>This only create sample categories, do not use on production environment</warning>');

        // Ask for Arguments
        $_argument = $this->askForArguments($input, $output);

        /**
         * Loop to create categories
         */
        for ($i = 0; $i < $_argument['category-number']; ++$i) {
            if (!is_null($_argument['category-name-prefix'])) {
                $name = $_argument['category-name-prefix'] . ' ' . $i;
            } else {
                $name = self::DEFAULT_CATEGORY_NAME . ' ' . $i;
            }

            /** @var Mage_Catalog_Model_Category $categoryModel */
            $categoryModel = Mage::getModel('catalog/category');

            // Check if product exists
            $collection = $categoryModel->getCollection();
            $collection
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('name', ['eq' => $name]);
            $_size = $collection->getSize();
            if ($_size > 0) {
                $output->writeln("<comment>CATEGORY: WITH NAME: '" . $name . "' EXISTS! Skip</comment>\r");
                ++$_argument['category-number'];
                continue;
            }

            unset($collection);

            $storeId = $_argument['store-id'];
            $rootCategoryId = Mage::app()->getStore($storeId)->getRootCategoryId();

            /** @var Mage_Catalog_Model_Category $category */
            $category = Mage::getModel('catalog/category');
            $category->setName($name);
            $category->setIsActive(self::DEFAULT_CATEGORY_STATUS); # @phpstan-ignore argument.type (needs to be fixed OpenMage)
            $category->setDisplayMode('PRODUCTS');
            $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR); # @phpstan-ignore argument.type (needs to be fixed OpenMage)
            $this->setCategoryStoreId($category, $storeId);
            /** @var Mage_Catalog_Model_Category $parentCategory */
            $parentCategory = Mage::getModel('catalog/category');
            $parentCategory->load($rootCategoryId);
            $category->setPath($parentCategory->getPath());

            $category->save();
            $parentCategoryId = $category->getId();
            $output->writeln(
                "<comment>CATEGORY: '" . $category->getName() . "' WITH ID: '" . $category->getId() .
                "' CREATED!</comment>",
            );
            unset($category);

            // Create children Categories
            for ($j = 0; $j < $_argument['children-categories-number']; ++$j) {
                $name_child = $name . ' child ' . $j;

                /** @var Mage_Catalog_Model_Category $category */
                $category = Mage::getModel('catalog/category');
                $category->setName($name_child);
                $category->setIsActive(self::DEFAULT_CATEGORY_STATUS); # @phpstan-ignore argument.type (needs to be fixed OpenMage)
                $category->setDisplayMode('PRODUCTS');
                $category->setIsAnchor(self::DEFAULT_CATEGORY_ANCHOR); # @phpstan-ignore argument.type (needs to be fixed OpenMage)
                $this->setCategoryStoreId($category, $storeId);
                /** @var Mage_Catalog_Model_Category $parentCategoryModel */
                $parentCategoryModel = Mage::getModel('catalog/category');
                $parentCategory = $parentCategoryModel->load($parentCategoryId);
                $category->setPath($parentCategory->getPath());

                $category->save();
                $output->writeln(
                    "<comment>CATEGORY CHILD: '" . $category->getName() . "' WITH ID: '" . $category->getId() .
                    "' CREATED!</comment>",
                );
                unset($category);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Ask for command arguments
     */
    private function askForArguments(InputInterface $input, OutputInterface $output): array
    {
        $questionHelper = $this->getQuestionHelper();
        $_argument = [];

        // Store ID
        if (is_null($input->getArgument('store-id'))) {
            /** @var Mage_Core_Model_Store $model */
            $model = Mage::getModel('core/store');
            $store_id = $model->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('store_id', ['gt' => 0])
                ->setOrder('store_id', 'ASC');
            $_store_ids = [];

            foreach ($store_id as $item) {
                $_store_ids[$item['store_id']] = $item['store_id'] . '|' . $item['code'];
            }

            $question = new ChoiceQuestion('Please select Store ID (default: 1)', $_store_ids, self::DEFAULT_STORE_ID);
            $question->setErrorMessage('Store ID "%s" is invalid.');
            $response = explode('|', (string) $questionHelper->ask($input, $output, $question));
            $input->setArgument('store-id', $response[0]);
        }

        $output->writeln('<info>Store ID selected: ' . $input->getArgument('store-id') . '</info>');
        $_argument['store-id'] = $input->getArgument('store-id');

        // Number of Categories
        if (is_null($input->getArgument('category-number'))) {
            $question = new Question('Please enter the number of categories to create (default 1): ', 1);
            $question->setValidator(function ($answer) {
                $answer = (int) $answer;
                if ($answer <= 0) {
                    throw new RuntimeException('Please enter an integer value or > 0');
                }

                return $answer;
            });
            $input->setArgument('category-number', $questionHelper->ask($input, $output, $question));
        }

        $output->writeln(
            '<info>Number of categories to create: ' . $input->getArgument('category-number') . '</info>',
        );
        $_argument['category-number'] = $input->getArgument('category-number');

        // Number of child categories
        if (is_null($input->getArgument('children-categories-number'))) {
            $question = new Question(
                "Number of children for each category created (default: 0 - use '-1' for random from 0 to 5): ",
                0,
            );
            $question->setValidator(function ($answer) {
                $answer = (int) $answer;
                if ($answer < -1) {
                    throw new RuntimeException('Please enter an integer value or >= -1');
                }

                return $answer;
            });
            $input->setArgument('children-categories-number', $questionHelper->ask($input, $output, $question));
        }

        if ($input->getArgument('children-categories-number') == -1) {
            $input->setArgument('children-categories-number', random_int(0, 5));
        }

        $output->writeln(
            '<info>Number of categories children to create: ' . $input->getArgument('children-categories-number') .
            '</info>',
        );
        $_argument['children-categories-number'] = $input->getArgument('children-categories-number');

        // Category name prefix
        if (is_null($input->getArgument('category-name-prefix'))) {
            $question = new Question(
                "Please enter the category name prefix (default '" . self::DEFAULT_CATEGORY_NAME . "'): ",
                self::DEFAULT_CATEGORY_NAME,
            );
            $input->setArgument('category-name-prefix', $questionHelper->ask($input, $output, $question));
        }

        $output->writeln('<info>CATEGORY NAME PREFIX: ' . $input->getArgument('category-name-prefix') . '</info>');
        $_argument['category-name-prefix'] = $input->getArgument('category-name-prefix');

        return $_argument;
    }

    /**
     * Setting the store-ID of a category requires a compatibility layer for Magento 1.5.1.0
     *
     * @param int|Mage_Core_Model_Store|string $storeId
     */
    private function setCategoryStoreId(Mage_Catalog_Model_Category $mageCatalogModelCategory, $storeId): void
    {
        $mageCatalogModelCategory->setStoreId($storeId);
    }
}
