<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use Exception;
use InvalidArgumentException;
use Mage;
use Mage_Catalog_Model_Category;
use Mage_Catalog_Model_Product;
use Mage_Customer_Model_Customer;
use Mage_Eav_Model_Entity_Attribute;
use Mage_Eav_Model_Resource_Entity_Attribute_Collection;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * System info command
 *
 * @package N98\Magento\Command\System
 */
class InfoCommand extends AbstractMagentoCommand
{
    protected array $infos;

    protected function configure(): void
    {
        $this
            ->setName('sys:info')
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                'Only output value of named param like "version". Key is case insensitive.',
            )->setDescription('Prints infos about the current magento system.')
            ->addFormatOption()
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);

        $softInitMode = in_array($input->getArgument('key'), ['version', 'edition']);

        if ($input->getOption('format') == null && $input->getArgument('key') == null) {
            $this->writeSection($output, 'Magento System Information');
        }

        $this->initMagento($softInitMode);

        $this->infos['Version'] = $this->magentoVersion();
        $this->infos['Edition'] = ($this->_magentoEnterprise ? 'Enterprise' : 'Community');
        $this->infos['Root'] = $this->_magentoRootFolder;

        if ($softInitMode === false) {
            $config = Mage::app()->getConfig();
            $this->addCacheInfos();

            $this->infos['Session'] = $config->getNode('global/session_save');

            $this->infos['Crypt Key'] = $config->getNode('global/crypt/key');
            $this->infos['Install Date'] = $config->getNode('global/install/date');
            try {
                $this->findCoreOverwrites();
                $this->findVendors();
                $this->attributeCount();
                $this->customerCount();
                $this->categoryCount();
                $this->productCount();
            } catch (Exception $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }
        }

        $table = [];
        foreach ($this->infos as $key => $value) {
            $table[] = [$key, $value];
        }

        if (($settingArgument = $input->getArgument('key')) !== null) {
            $settingArgument = strtolower($settingArgument);
            $this->infos = array_change_key_case($this->infos, CASE_LOWER);
            if (!isset($this->infos[$settingArgument])) {
                throw new InvalidArgumentException('Unknown key: ' . $settingArgument);
            }

            $output->writeln((string) $this->infos[$settingArgument]);
        } else {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['name', 'value'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        }

        return Command::SUCCESS;
    }

    protected function magentoVersion(): string
    {
        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists('Mage', 'getOpenMageVersion')) {
            return 'OpenMage LTS ' . Mage::getOpenMageVersion();
        }

        return Mage::getVersion();
    }

    protected function addCacheInfos(): void
    {
        $this->infos['Cache Backend'] = get_class(Mage::app()->getCache()->getBackend());

        switch (get_class(Mage::app()->getCache()->getBackend())) {
            case 'Zend_Cache_Backend_File':
                $cacheDir = Mage::app()->getConfig()->getOptions()->getCacheDir();
                $this->infos['Cache Directory'] = $cacheDir;
                break;

            default:
        }
    }

    protected function findCoreOverwrites(): void
    {
        $folders = [$this->_magentoRootFolder . '/app/code/local/Mage', $this->_magentoRootFolder . '/app/code/local/Enterprise', $this->_magentoRootFolder . '/app/code/community/Mage', $this->_magentoRootFolder . '/app/code/community/Enterprise'];
        foreach ($folders as $key => $folder) {
            if (!is_dir($folder)) {
                unset($folders[$key]);
            }
        }

        if ($folders !== []) {
            $finder = Finder::create();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->in($folders);
            $this->infos['Core Autoloader Overwrites'] = $finder->count();
        }
    }

    protected function findVendors(): void
    {
        $codePools = [
            'core'      => $this->_magentoRootFolder . '/app/code/core/',
            'community' => $this->_magentoRootFolder . '/app/code/community/',
        ];

        if (is_dir($this->_magentoRootFolder . '/app/code/local/')) {
            $codePools['local'] = $this->_magentoRootFolder . '/app/code/local/';
        }

        foreach ($codePools as $codePool => $codePoolDir) {
            $finder = Finder::create();
            $finder
                ->directories()
                ->ignoreUnreadableDirs(true)
                ->in($codePoolDir)
                ->depth(0)
                ->sortByName();

            $vendors = iterator_to_array($finder);
            $vendors = array_map(
                function ($value) use ($codePoolDir) {
                    return str_replace($codePoolDir, '', (string) $value);
                },
                $vendors,
            );

            $this->infos['Vendors (' . $codePool . ')'] = implode(', ', $vendors);
        }
    }

    protected function categoryCount(): void
    {
        /** @var Mage_Catalog_Model_Category $model */
        $model = Mage::getModel('catalog/category');
        $this->infos['Category Count'] = $model->getCollection()->getSize();
    }

    protected function productCount(): void
    {
        /** @var Mage_Catalog_Model_Product $model */
        $model = Mage::getModel('catalog/product');
        $this->infos['Product Count'] = $model->getCollection()->getSize();
    }

    protected function customerCount(): void
    {
        /** @var Mage_Customer_Model_Customer $model */
        $model = Mage::getModel('customer/customer');
        $this->infos['Customer Count'] = $model->getCollection()->getSize();
    }

    protected function attributeCount(): void
    {
        /** @var Mage_Eav_Model_Entity_Attribute $model */
        $model = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = $model->getCollection();
        $this->infos['Attribute Count'] = $collection->getSize();
    }
}
