<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Mage_Eav_Model_Entity_Attribute;
use Varien_Db_Adapter_Interface;

/**
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
interface EntityType
{
    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     */
    public function __construct(Mage_Eav_Model_Entity_Attribute $attribute);

    /**
     * @param Varien_Db_Adapter_Interface|false $connection
     * @return void
     */
    public function setReadConnection($connection): void;

    /**
     * @return array<int, string>
     */
    public function getWarnings(): array;

    /**
     * @return string
     */
    public function generateCode(): string;
}
