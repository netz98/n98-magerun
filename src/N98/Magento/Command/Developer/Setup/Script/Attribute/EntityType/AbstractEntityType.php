<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

abstract class AbstractEntityType
{
    /**
     * @var \Varien_Db_Adapter_Interface
     */
    protected $readConnection;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var array
     */
    protected $warnings = array();

    /**
     * @param \Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @param $connection
     */
    public function setReadConnection($connection)
    {
        $this->readConnection = $connection;
    }

    /**
     * @param array $warnings
     */
    public function setWarnings($warnings)
    {
        $this->warnings = $warnings;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Gets attribute labels from database
     *
     * @param \Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return array
     */
    public function getAttributeLabels($attribute)
    {
        $select = $this->readConnection->select()
            ->from($this->readConnection->getTableName('eav_attribute_label'))
            ->where('attribute_id = ?', $attribute->getId());

        $query = $select->query();

        $attributeLabels = array();
        foreach ($query->fetchAll() as $row) {
            $attributeLabels[$row['store_id']] = $row['value'];
        }

        return $attributeLabels;
    }

    /**
     * Gets attribute options from database
     *
     * @param \Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return array
     */
    protected function getOptions($attribute)
    {
        $select = $this->readConnection->select()
            ->from(array('o' => $this->readConnection->getTableName('eav_attribute_option')))
            ->join(
                array('ov' => $this->readConnection->getTableName('eav_attribute_option_value')),
                'o.option_id = ov.option_id')
            ->where('o.attribute_id = ?', $attribute->getId())
            ->where('ov.store_id = 0')
            ->order('ov.option_id');

        $query = $select->query();

        $values = array();
        foreach ($query->fetchAll() as $row) {
            $values[] = $row['value'];
        }

        return array('values' => $values);
    }
}