<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Config;
use Mage_Eav_Model_Entity_Attribute_Abstract;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * View EAV attribute command
 *
 * @package N98\Magento\Command\Eav\Attribute
 */
class ViewCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('eav:attribute:view')
            ->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
            ->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute Code')
            ->setDescription('View informations about an EAV attribute')
            ->addFormatOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $entityType = $input->getArgument('entityType');
        $attributeCode = $input->getArgument('attributeCode');

        $attribute = $this->getAttribute($entityType, $attributeCode);
        if (!$attribute) {
            throw new InvalidArgumentException('Attribute was not found.');
        }

        /** @var array|false $cacheIdTags */
        $cacheIdTags    = $attribute->getCacheIdTags();
        /** @var array|false $cacheTags */
        $cacheTags      = $attribute->getCacheTags();
        $flatColumns    = $attribute->getFlatColumns();

        $table = [
            ['ID', $attribute->getId()],
            ['Code', $attribute->getName()],
            ['Attribute-Set-ID', $attribute->getAttributeSetId()],
            ['Visible-On-Front', $attribute->getIsVisibleOnFront() ? 'yes' : 'no'],
            ['Attribute-Model', $attribute->getAttributeModel() ?: ''],
            ['Backend-Model', $attribute->getBackendModel() ?: ''],
            ['Backend-Table', $attribute->getBackendTable() ?: ''],
            ['Backend-Type', $attribute->getBackendType() ?: ''],
            ['Source-Model', $attribute->getSourceModel() ?: ''],
            ['Cache-ID-Tags', $cacheIdTags ? implode(',', $cacheIdTags) : ''],
            ['Cache-Tags', $cacheTags ? implode(',', $cacheTags) : ''],
            ['Default-Value', $attribute->getDefaultValue() ?: ''],
            ['Flat-Columns', $flatColumns ? implode(',', array_keys($flatColumns)) : ''],
        ];

        $flatIndexes = $attribute->getFlatIndexes() ? $attribute->getFlatIndexes() : '';
        if ($flatIndexes) {
            $key = array_key_first($flatIndexes);
            $flatIndexes = implode(',', $flatIndexes[$key]['fields']);
            $table[] = ['Flat-Indexes', $key . ' - ' . $flatIndexes];
        } else {
            $table[] = ['Flat-Indexes', ''];
        }

        if ($attribute->getFrontend()) {
            $table[] = ['Frontend-Label', $attribute->getFrontend()->getLabel()];
            $table[] = ['Frontend-Class', trim($attribute->getFrontend()->getClass())];
            $table[] = ['Frontend-Input', trim($attribute->getFrontend()->getInputType())];
            $table[] = ['Frontend-Input-Renderer-Class', trim((string) $attribute->getFrontend()->getInputRendererClass())];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Type', 'Value'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }

    /**
     * @return Mage_Eav_Model_Entity_Attribute_Abstract|false
     * @throws Mage_Core_Exception
     */
    protected function getAttribute(string $entityType, string $attributeCode)
    {
        /** @var Mage_Eav_Model_Config $model */
        $model = Mage::getModel('eav/config');
        return $model->getAttribute($entityType, $attributeCode);
    }
}
