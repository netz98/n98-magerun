<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Url;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Store;
use Mage_Core_Model_Store_Exception;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Object;

/**
 * Create url list
 *
 * Examples:
 * - Create a list of product urls only
 *     ./n98-magerun.phar system:urls:list --add-products 4
 *
 * - Create a list of all products, categories and cms pages of store 4 and 5 separating host and path (e.g. to feed a
 *   jmeter csv sampler)
 *     ./n98-magerun.phar system:urls:list --add-all 4,5 '{host},{path}' > urls.csv
 *
 * The "linetemplate" can contain all parts "parse_url" return wrapped in '{}'. '{url}' always maps the complete url
 * and is set by default
 *
 * @author Fabrizio Branca
 * @package N98\Magento\Command\System\Url
 */
class ListCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_STORES = 'stores';

    public const COMMAND_ARGUMENT_LINE_TEMPLATE = 'linetemplate';

    public const COMMAND_OPTION_ADD_ALL = 'add-all';

    public const COMMAND_OPTION_ADD_CATEGORIES = 'add-categories';

    public const COMMAND_OPTION_ADD_CMS_PAGES = 'add-cmspages';

    public const COMMAND_OPTION_ADD_PRODUCTS = 'add-products';

    /**
     * @var string
     */
    protected static $defaultName = 'sys:url:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Get all urls.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_STORES,
                InputArgument::OPTIONAL,
                'Stores (comma-separated list of store ids)'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_LINE_TEMPLATE,
                InputArgument::OPTIONAL,
                'Line template',
                '{url}'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_CATEGORIES,
                null,
                InputOption::VALUE_NONE,
                'Adds categories'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_PRODUCTS,
                null,
                InputOption::VALUE_NONE,
                'Adds products'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_CMS_PAGES,
                null,
                InputOption::VALUE_NONE,
                'Adds cms pages'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_ALL,
                null,
                InputOption::VALUE_NONE,
                'Adds categories, products and cms pages'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Examples:

- Create a list of product urls only:

   $ n98-magerun.phar sys:url:list --add-products 4

- Create a list of all products, categories and cms pages of store 4 
  and 5 separating host and path (e.g. to feed a jmeter csv sampler):

   $ n98-magerun.phar sys:url:list --add-all 4,5 '{host},{path}' > urls.csv

- The "linetemplate" can contain all parts "parse_url" return wrapped 
  in '{}'. '{url}' always maps the complete url and is set by default
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function interact(InputInterface $input,OutputInterface $output): void
    {
        if ($input->getOption(self::COMMAND_OPTION_ADD_ALL)) {
            $input->setOption(self::COMMAND_OPTION_ADD_CATEGORIES, true);
            $input->setOption(self::COMMAND_OPTION_ADD_PRODUCTS, true);
            $input->setOption(self::COMMAND_OPTION_ADD_CMS_PAGES, true);
        }

        $stores = [0];
        if ($input->getOption(self::COMMAND_OPTION_ADD_CATEGORIES)
            || $input->getOption(self::COMMAND_OPTION_ADD_PRODUCTS)
        ) {
            $stores = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_STORES, $input, $output);
            $stores = explode(',', $stores);
        }
        $input->setArgument(self::COMMAND_ARGUMENT_STORES, $stores);
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $urls = [];

        /** @var array<int, string> $stores */
        $stores = $input->getArgument(self::COMMAND_ARGUMENT_STORES);
        foreach ($stores as $storeId) {
            try {
                /** @var Mage_Core_Model_Store $currentStore */
                $currentStore = $this->_getMage()->getStore($storeId);
            } catch (Mage_Core_Model_Store_Exception $exception) {
                $output->writeln(sprintf('<error>Store with ID %s not found. Skipped.</error>', $storeId));
                continue;
            }

            // base url
            $urls[] = $currentStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

            $linkBaseUrl = $currentStore->getBaseUrl();

            if ($input->getOption(self::COMMAND_OPTION_ADD_CATEGORIES)) {
                $urls = $this->getUrls('sitemap/catalog_category', $linkBaseUrl, $storeId, $urls);
            }

            if ($input->getOption(self::COMMAND_OPTION_ADD_PRODUCTS)) {
                $urls = $this->getUrls('sitemap/catalog_product', $linkBaseUrl, $storeId, $urls);
            }

            if ($input->getOption(self::COMMAND_OPTION_ADD_CMS_PAGES)) {
                $urls = $this->getUrls('sitemap/cms_page', $linkBaseUrl, $storeId, $urls);
            }
        }

        if ($urls === []) {
            return Command::FAILURE;
        }


        foreach ($urls as $url) {
            // pre-process
            /** @var string $line */
            $line = $input->getArgument(self::COMMAND_ARGUMENT_LINE_TEMPLATE);
            $line = str_replace('{url}', $url, $line);

            /** @var array<string, string> $parts */
            $parts = parse_url($url);

            /**
             * @var string $key
             * @var string $value
             */
            foreach ($parts as $key => $value) {
                $line = str_replace('{' . $key . '}', $value, $line);
            }

            // ... and output
            $output->writeln($line);
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $resourceModel
     * @param string $linkBaseUrl
     * @param string $storeId
     * @param array<int, string> $urls
     * @return array<int, string>
     */
    protected function getUrls(string $resourceModel, string $linkBaseUrl, string $storeId, array $urls): array
    {
        $resourceModel = Mage::getResourceModel($resourceModel);
        if (!$resourceModel || !method_exists($resourceModel, 'getCollection')) {
            return $urls;
        }

        $collection = $resourceModel->getCollection($storeId);
        if (!$collection) {
            return $urls;
        }

        foreach ($collection as $item) {
            /** @var Varien_Object $item */
            $urls[] = $linkBaseUrl . $item->getUrl();
        }
        return $urls;
    }
}
