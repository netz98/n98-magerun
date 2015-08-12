<?php

namespace N98\Magento\Command\System\Url;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create url list
 *
 * Examples:
 * - Create a list of product urls only
 *     ./n98-magerun.phar system:urls:list --add-products 4
 *
 * - Create a list of all products, categories and cms pages of store 4 and 5 separating host and path (e.g. to feed a jmeter csv sampler)
 *     ./n98-magerun.phar system:urls:list --add-all 4,5 '{host},{path}' > urls.csv
 *
 * The "linetemplate" can contain all parts "parse_url" return wrapped in '{}'. '{url}' always maps the complete url and is set by default
 *
 * @author Fabrizio Branca
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:url:list')
            ->addOption('add-categories', null, InputOption::VALUE_NONE, 'Adds categories')
            ->addOption('add-products', null, InputOption::VALUE_NONE, 'Adds products')
            ->addOption('add-cmspages', null, InputOption::VALUE_NONE, 'Adds cms pages')
            ->addOption('add-all', null, InputOption::VALUE_NONE, 'Adds categories, products and cms pages')
            ->addArgument('stores', InputArgument::OPTIONAL, 'Stores (comma-separated list of store ids)')
            ->addArgument('linetemplate', InputArgument::OPTIONAL, 'Line template', '{url}')
            ->setDescription('Get all urls.')
        ;

        $help = <<<HELP
Examples:

- Create a list of product urls only:

   $ n98-magerun.phar sys:url:list --add-products 4

- Create a list of all products, categories and cms pages of store 4 and 5 separating host and path (e.g. to feed a jmeter csv sampler):

   $ n98-magerun.phar sys:url:list --add-all 4,5 '{host},{path}' > urls.csv

- The "linetemplate" can contain all parts "parse_url" return wrapped in '{}'. '{url}' always maps the complete url and is set by default
HELP;
        $this->setHelp($help);
    }

    /**
     * Execute command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \InvalidArgumentException
     * @throws \Mage_Core_Model_Store_Exception
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getOption('add-all')) {
            $input->setOption('add-categories', true);
            $input->setOption('add-products', true);
            $input->setOption('add-cmspages', true);
        }

        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            $stores = explode(',', $input->getArgument('stores'));

            $urls = array();

            foreach ($stores as $storeId) {

                $currentStore = \Mage::app()->getStore($storeId); /* @var $currentStore \Mage_Core_Model_Store */

                // base url
                $urls[] = $currentStore->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_WEB);

                $linkBaseUrl = $currentStore->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_LINK);

                if ($input->getOption('add-categories')) {
                    $urls = $this->getUrls('sitemap/catalog_category', $linkBaseUrl, $storeId, $urls);
                }

                if ($input->getOption('add-products')) {
                    $urls = $this->getUrls('sitemap/catalog_product', $linkBaseUrl, $storeId, $urls);
                }

                if ($input->getOption('add-cmspages')) {
                    $urls = $this->getUrls('sitemap/cms_page', $linkBaseUrl, $storeId, $urls);
                }
            }

            if (count($urls) === 0) {
                return;
            }

            foreach ($urls as $url) {

                // pre-process
                $line = $input->getArgument('linetemplate');
                $line = str_replace('{url}', $url, $line);

                $parts = parse_url($url);
                foreach ($parts as $key => $value) {
                    $line = str_replace('{' . $key . '}', $value, $line);
                }

                // ... and output
                $output->writeln($line);
            }

        }
    }

    /**
     * @param $resourceModel
     * @param $linkBaseUrl
     * @param $storeId
     * @param $urls
     * @return array
     */
    protected function getUrls($resourceModel, $linkBaseUrl, $storeId, $urls) {
        $collection = \Mage::getResourceModel($resourceModel)->getCollection($storeId);
        if (!$collection) {
            return $urls;
        }
        foreach ($collection as $item) {
            /* @var $item \Varien_Object */
            $urls[] = $linkBaseUrl . $item->getUrl();
        }
        return $urls;
    }
}
