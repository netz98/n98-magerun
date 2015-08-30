<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

interface EntityType
{
    /**
     * @param \Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function __construct(\Mage_Eav_Model_Entity_Attribute $attribute);
    public function setReadConnection($connection);
    public function getWarnings();
    public function generateCode();
}
