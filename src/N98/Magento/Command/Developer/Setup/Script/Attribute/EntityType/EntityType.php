<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Mage_Eav_Model_Entity_Attribute;

interface EntityType
{
    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function __construct(Mage_Eav_Model_Entity_Attribute $attribute);

    /**
     * @param $connection
     * @return void
     */
    public function setReadConnection($connection);

    /**
     * @return array
     */
    public function getWarnings();

    /**
     * @return string
     */
    public function generateCode();
}
