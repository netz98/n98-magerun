<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Url;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Store;
use Mage_Sitemap_Model_Resource_Catalog_Category;
use Mage_Sitemap_Model_Resource_Catalog_Product;
use Mage_Sitemap_Model_Resource_Cms_Page;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Object;

/**
 * List url command
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
 * @package N98\Magento\Command\System\Url
 *
 * @author Fabrizio Branca
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:url:list')
            ->addOption('add-categories', null, InputOption::VALUE_NONE, 'Adds categories')
            ->addOption('add-products', null, InputOption::VALUE_NONE, 'Adds products')
            ->addOption('add-cmspages', null, InputOption::VALUE_NONE, 'Adds cms pages')
            ->addOption('add-all', null, InputOption::VALUE_NONE, 'Adds categories, products and cms pages')
            ->addArgument('stores', InputArgument::OPTIONAL, 'Stores (comma-separated list of store ids)')
            ->addArgument('linetemplate', InputArgument::OPTIONAL, 'Line template', '{url}')
            ->setDescription('Get all urls.');
    }

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        if ($input->getOption('add-all')) {
            $input->setOption('add-categories', true);
            $input->setOption('add-products', true);
            $input->setOption('add-cmspages', true);
        }

        $stores = explode(',', (string) $input->getArgument('stores'));

        $urls = [];

        foreach ($stores as $store) {
            /** @var Mage_Core_Model_Store $currentStore */
            $currentStore = Mage::app()->getStore($store);

            // base url
            $urls[] = $currentStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

            $linkBaseUrl = $currentStore->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

            if ($input->getOption('add-categories')) {
                $urls = $this->getUrls('sitemap/catalog_category', $linkBaseUrl, $store, $urls);
            }

            if ($input->getOption('add-products')) {
                $urls = $this->getUrls('sitemap/catalog_product', $linkBaseUrl, $store, $urls);
            }

            if ($input->getOption('add-cmspages')) {
                $urls = $this->getUrls('sitemap/cms_page', $linkBaseUrl, $store, $urls);
            }
        }

        if (count($urls) === 0) {
            return Command::SUCCESS;
        }

        foreach ($urls as $url) {
            // pre-process
            $line = $input->getArgument('linetemplate');
            $line = str_replace('{url}', $url, $line);

            /** @var array $parts */
            $parts = parse_url($url);
            foreach ($parts as $key => $value) {
                $line = str_replace('{' . $key . '}', $value, $line);
            }

            // ... and output
            $output->writeln($line);
        }

        return Command::SUCCESS;
    }

    /**
     * @param 'sitemap/catalog_category'|'sitemap/catalog_product'|'sitemap/cms_page' $resourceModelAlias
     * @param string $linkBaseUrl
     * @param string $storeId
     *
     * @return array
     */
    protected function getUrls($resourceModelAlias, $linkBaseUrl, $storeId, array $urls)
    {
        $resourceModel = Mage::getResourceModel($resourceModelAlias);
        if (!$resourceModel instanceof Mage_Sitemap_Model_Resource_Catalog_Category &&
            !$resourceModel instanceof Mage_Sitemap_Model_Resource_Catalog_Product &&
            !$resourceModel instanceof Mage_Sitemap_Model_Resource_Cms_Page
        ) {
            return $urls;
        }

        $collection = $resourceModel->getCollection($storeId);
        if (!$collection) {
            return $urls;
        }

        foreach ($collection as $item) {
            $urls[] = $linkBaseUrl . $item->getUrl();
        }

        return $urls;
    }
}
