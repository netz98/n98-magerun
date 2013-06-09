<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

use Installer\Exception;

class Factory
{
    public static function create($entityType, $attribute)
    {
        $words = explode('_', strtolower($entityType));
        $class = __NAMESPACE__ . '\\';
        foreach ($words as $word) {
            $class .= ucfirst(trim($word));
        }

        if (!class_exists($class)) {
            throw new \Exception('No script generator for this entity type available');
        }

        return new $class($attribute);
    }
}