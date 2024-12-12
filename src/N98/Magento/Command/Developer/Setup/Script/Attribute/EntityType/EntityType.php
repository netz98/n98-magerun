<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Mage_Eav_Model_Entity_Attribute;
use Varien_Db_Adapter_Interface;

/**
 * EntityType interface
 *
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
interface EntityType
{
    public function __construct(Mage_Eav_Model_Entity_Attribute $mageEavModelEntityAttribute);

    public function setReadConnection(Varien_Db_Adapter_Interface $varienDbAdapter): void;

    public function getWarnings(): array;

    public function generateCode(): string;
}
