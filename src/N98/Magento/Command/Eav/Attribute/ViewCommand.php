<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Entity_Attribute_Abstract;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * EAV attribute view command
 *
 * @package N98\Magento\Command\Eav\Attribute
 */
class ViewCommand extends AbstractCommand implements CommandDataInterface
{
    public const COMMAND_ARGUMENT_ENTITY = 'entityType';

    public const COMMAND_ARGUMENT_ATTRIBUTE = 'attributeCode';

    /**
     * @var string
     */
    protected static $defaultName = 'eav:attribute:view';

    /**
     * @var string
     */
    protected static $defaultDescription = 'View information about an EAV attribute.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ENTITY,
                InputArgument::REQUIRED,
                'Entity Type Code like catalog_product'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ATTRIBUTE,
                InputArgument::REQUIRED,
                'Attribute Code'
            )
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Exception
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        /** @var string $entityType */
        $entityType = $input->getArgument(self::COMMAND_ARGUMENT_ENTITY);
        /** @var string $attributeCode */
        $attributeCode = $input->getArgument(self::COMMAND_ARGUMENT_ATTRIBUTE);

        $attribute = $this->getAttribute($entityType, $attributeCode);
        if (!$attribute) {
            throw new InvalidArgumentException('Attribute was not found.');
        }

        $cacheIdTags = $attribute->getCacheIdTags();
        $cacheTags = $attribute->getCacheTags();
        $flatColumns = $attribute->getFlatColumns();

        $this->data = [
            ['ID', $attribute->getId()],
            ['Code', $attribute->getName()],
            ['Attribute-Set-ID', $attribute->getAttributeSetId()],
            ['Visible-On-Front', $attribute->getIsVisibleOnFront() ? 'yes' : 'no'],
            ['Attribute-Model', $attribute->getAttributeModel() ?: ''],
            ['Backend-Model', $attribute->getBackendModel() ?: ''],
            ['Backend-Table', $attribute->getBackendTable() ?: ''],
            ['Backend-Type', $attribute->getBackendType() ?: ''],
            ['Source-Model', $attribute->getSourceModel() ?: ''],
            ['Cache-ID-Tags', is_array($cacheIdTags) ? implode(',', $cacheIdTags) : ''],
            ['Cache-Tags', is_array($cacheTags) ? implode(',', $cacheTags) : ''],
            ['Default-Value', $attribute->getDefaultValue() ?: ''],
            ['Flat-Columns', is_array($flatColumns) ? implode(',', array_keys($flatColumns)) : '']
        ];

        $key = '';
        $flatIndexes = $attribute->getFlatIndexes() ? $attribute->getFlatIndexes() : '';
        if ($flatIndexes) {
            $key = array_key_first($flatIndexes);
            $flatIndexes = implode(',', $flatIndexes[$key]['fields']);
        }
        $this->data[] = ['Flat-Indexes', $flatIndexes ? $key . ' - ' . $flatIndexes : ''];

        if ($attribute->getFrontend()) {
            $this->data[] = ['Frontend-Label', $attribute->getFrontend()->getLabel()];
            $this->data[] = ['Frontend-Class', trim($attribute->getFrontend()->getClass())];
            $this->data[] = ['Frontend-Input', trim($attribute->getFrontend()->getInputType())];
            $this->data[] = ['Frontend-Input-Renderer-Class', trim((string)$attribute->getFrontend()->getInputRendererClass())];
        }
    }

    /**
     * @return array<int, string>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    protected function getTableHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Type', 'Value'];
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     * @return Mage_Eav_Model_Entity_Attribute_Abstract|false
     * @throws Mage_Core_Exception
     */
    protected function getAttribute(string $entityType, string $attributeCode)
    {
        return Mage::getModel('eav/config')->getAttribute($entityType, $attributeCode);
    }
}
