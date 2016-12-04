<?php

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $entityType = $input->getArgument('entityType');
        $attributeCode = $input->getArgument('attributeCode');

        $attribute = $this->getAttribute($entityType, $attributeCode);
        if (!$attribute) {
            throw new InvalidArgumentException('Attribute was not found.');
        }

        $table = array(
            array('ID', $attribute->getId()),
            array('Code', $attribute->getName()),
            array('Attribute-Set-ID', $attribute->getAttributeSetId()),
            array('Visible-On-Front', $attribute->getIsVisibleOnFront() ? 'yes' : 'no'),
            array('Attribute-Model', $attribute->getAttributeModel() ? $attribute->getAttributeModel() : ''),
            array('Backend-Model', $attribute->getBackendModel() ? $attribute->getBackendModel() : ''),
            array('Backend-Table', $attribute->getBackendTable() ? $attribute->getBackendTable() : ''),
            array('Backend-Type', $attribute->getBackendType() ? $attribute->getBackendType() : ''),
            array('Source-Model', $attribute->getSourceModel() ? $attribute->getSourceModel() : ''),
            array('Cache-ID-Tags', $attribute->getCacheIdTags() ? implode(',', $attribute->getCacheIdTags()) : ''),
            array('Cache-Tags', $attribute->getCacheTags() ? implode(',', $attribute->getCacheTags()) : ''),
            array('Default-Value', $attribute->getDefaultValue() ? $attribute->getDefaultValue() : ''),
            array(
                'Flat-Columns',
                $attribute->getFlatColumns() ? implode(',', array_keys($attribute->getFlatColumns())) : '',
            ),
            array('Flat-Indexes', $attribute->getFlatIndexes() ? implode(',', $attribute->getFlatIndexes()) : ''),
        );

        if ($attribute->getFrontend()) {
            $table[] = array('Frontend-Label', $attribute->getFrontend()->getLabel());
            $table[] = array('Frontend-Class', trim($attribute->getFrontend()->getClass()));
            $table[] = array('Frontend-Input', trim($attribute->getFrontend()->getInputType()));
            $table[] = array(
                'Frontend-Input-Renderer-Class',
                trim($attribute->getFrontend()->getInputRendererClass()),
            );
        }

        $this
            ->getHelper('table')
            ->setHeaders(array('Type', 'Value'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     *
     * @return \Mage_Eav_Model_Entity_Attribute_Abstract|false
     */
    protected function getAttribute($entityType, $attributeCode)
    {
        return \Mage::getModel('eav/config')->getAttribute($entityType, $attributeCode);
    }
}
