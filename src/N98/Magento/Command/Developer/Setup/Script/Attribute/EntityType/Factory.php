<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use RuntimeException;

/**
 * @package N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType
 */
class Factory
{
    /**
     * @param string $entityType
     * @param string $attribute
     * @return mixed
     */
    public static function create(string $entityType, string $attribute)
    {
        $words = explode('_', strtolower($entityType));
        $class = __NAMESPACE__ . '\\';
        foreach ($words as $word) {
            $class .= ucfirst(trim($word));
        }

        if (!class_exists($class)) {
            throw new RuntimeException('No script generator for this entity type available');
        }

        return new $class($attribute);
    }
}
