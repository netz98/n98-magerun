<?php

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ViewCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:attribute:view')
            ->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
            ->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute Code')
            ->setDescription('View informations about an EAV attribute')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $entityType = $input->getArgument('entityType');
        $attributeCode = $input->getArgument('attributeCode');

        $attribute = $this->getAttribute($entityType, $attributeCode);
        if (!$attribute) {
            throw new InvalidArgumentException('Attribute was not found.');
        }

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
            ['Cache-ID-Tags', $attribute->getCacheIdTags() ? implode(',', $attribute->getCacheIdTags()) : ''],
            ['Cache-Tags', $attribute->getCacheTags() ? implode(',', $attribute->getCacheTags()) : ''],
            ['Default-Value', $attribute->getDefaultValue() ?: ''],
            ['Flat-Columns', $attribute->getFlatColumns() ? implode(',', array_keys($attribute->getFlatColumns())) : '']
        ];

        $flatIndexes = $attribute->getFlatIndexes() ? $attribute->getFlatIndexes() : '';
        if ($flatIndexes) {
            $key = array_key_first($flatIndexes);
            $flatIndexes = implode(',', $flatIndexes[$key]['fields']);
        }
        $table[] = ['Flat-Indexes', $flatIndexes ? $key . ' - ' . $flatIndexes : ''];

        if ($attribute->getFrontend()) {
            $table[] = ['Frontend-Label', $attribute->getFrontend()->getLabel()];
            $table[] = ['Frontend-Class', trim($attribute->getFrontend()->getClass())];
            $table[] = ['Frontend-Input', trim($attribute->getFrontend()->getInputType())];
            $table[] = ['Frontend-Input-Renderer-Class', trim($attribute->getFrontend()->getInputRendererClass())];
        }

        $this
            ->getHelper('table')
            ->setHeaders(['Type', 'Value'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     *
     * @return \Mage_Eav_Model_Entity_Attribute_Abstract|false
     */
    protected function getAttribute($entityType, $attributeCode)
    {
        return Mage::getModel('eav/config')->getAttribute($entityType, $attributeCode);
    }
}
