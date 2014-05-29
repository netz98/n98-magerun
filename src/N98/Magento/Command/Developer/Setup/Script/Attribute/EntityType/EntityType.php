<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

interface EntityType
{
    /**
     * @param \Mage_Eav_Model_Entity_Attribute $attributeCode
     */
    public function __construct($attributeCode);
    public function setReadConnection($connection);
    public function getWarnings();
    public function generateCode();
}