<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Mage;
use Mage_Core_Model_Resource;
use Mage_Eav_Model_Entity_Attribute;
use Varien_Db_Adapter_Interface;

/**
 * Class AbstractEntityType
 *
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
abstract class AbstractEntityType implements EntityType
{
    protected Varien_Db_Adapter_Interface $readConnection;

    protected Mage_Eav_Model_Entity_Attribute $attribute;

    protected string $entityType;

    protected array $warnings = [];

    public function __construct(Mage_Eav_Model_Entity_Attribute $mageEavModelEntityAttribute)
    {
        $this->attribute = $mageEavModelEntityAttribute;
    }

    public function setReadConnection(Varien_Db_Adapter_Interface $varienDbAdapter): void
    {
        $this->readConnection = $varienDbAdapter;
    }

    public function setWarnings(array $warnings): void
    {
        $this->warnings = $warnings;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Gets attribute labels from database
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function getAttributeLabels($attribute): array
    {
        // FIXME: after having this warning in for some time, promote to a parameter type-hint.
        if (!$attribute instanceof Mage_Eav_Model_Entity_Attribute) {
            trigger_error(
                sprintf('Attribute not of type Mage_Eav_Model_Entity_Attribute, is of type %s', get_class($attribute)),
            );
        }

        /** @var Mage_Core_Model_Resource $resourceModel */
        $resourceModel = Mage::getSingleton('core/resource');
        $select = $this->readConnection->select()
            ->from($resourceModel->getTableName('eav_attribute_label'))
            ->where('attribute_id = ?', $attribute->getId());

        $query = $select->query();

        $attributeLabels = [];
        foreach ($query->fetchAll() as $row) {
            $attributeLabels[$row['store_id']] = $row['value'];
        }

        return $attributeLabels;
    }

    /**
     * Gets attribute options from database
     */
    protected function getOptions(Mage_Eav_Model_Entity_Attribute $mageEavModelEntityAttribute): array
    {
        /** @var Mage_Core_Model_Resource $resourceModel */
        $resourceModel = Mage::getSingleton('core/resource');
        $select = $this->readConnection->select()
            ->from(['o' => $resourceModel->getTableName('eav_attribute_option')])
            ->join(
                ['ov' => $resourceModel->getTableName('eav_attribute_option_value')],
                'o.option_id = ov.option_id',
            )
            ->where('o.attribute_id = ?', $mageEavModelEntityAttribute->getId())
            ->where('ov.store_id = 0')
            ->order('ov.option_id');

        $query = $select->query();

        $values = [];
        foreach ($query->fetchAll() as $row) {
            $values[] = $row['value'];
        }

        return ['values' => $values];
    }
}
