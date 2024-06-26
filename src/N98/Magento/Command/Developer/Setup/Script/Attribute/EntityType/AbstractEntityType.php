<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Mage;
use Mage_Eav_Model_Entity_Attribute;
use Varien_Db_Adapter_Interface;

/**
 * Class AbstractEntityType
 *
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
abstract class AbstractEntityType implements EntityType
{
    /**
     * @var Varien_Db_Adapter_Interface
     */
    protected Varien_Db_Adapter_Interface $readConnection;

    /**
     * @var Mage_Eav_Model_Entity_Attribute
     */
    protected Mage_Eav_Model_Entity_Attribute $attribute;

    /**
     * @var string
     */
    protected string $entityType;

    /**
     * @var array<int, string>
     */
    protected array $warnings = [];

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function __construct(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @param Varien_Db_Adapter_Interface $connection
     */
    public function setReadConnection($connection): void
    {
        $this->readConnection = $connection;
    }

    /**
     * @param array<int, string> $warnings
     */
    public function setWarnings(array $warnings): void
    {
        $this->warnings = $warnings;
    }

    /**
     * @return array<int, string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Gets attribute labels from database
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return array<string, string>
     */
    public function getAttributeLabels($attribute): array
    {
        // FIXME: after having this warning in for some time, promote to a parameter type-hint.
        if (!$attribute instanceof Mage_Eav_Model_Entity_Attribute) {
            trigger_error(
                sprintf('Attribute not of type Mage_Eav_Model_Entity_Attribute, is of type %s', get_class($attribute))
            );
        }

        $select = $this->readConnection->select()
            ->from(Mage::getSingleton('core/resource')->getTableName('eav_attribute_label'))
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
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return array<string, array<int, string>>
     */
    protected function getOptions(Mage_Eav_Model_Entity_Attribute $attribute): array
    {
        $resourceModel = Mage::getSingleton('core/resource');
        $select = $this->readConnection->select()
            ->from(['o' => $resourceModel->getTableName('eav_attribute_option')])
            ->join(
                ['ov' => $resourceModel->getTableName('eav_attribute_option_value')],
                'o.option_id = ov.option_id'
            )
            ->where('o.attribute_id = ?', $attribute->getId())
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
