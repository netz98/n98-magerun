<?php

declare(strict_types=1);

namespace N98\Util\Validator;

use Symfony\Component\Validator\Exception\NoSuchMetadataException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;

/**
 * Class FakeMetadataFactory
 *
 * @package N98\Util\Validator
 */
class FakeMetadataFactory implements MetadataFactoryInterface
{
    protected array $metadatas = [];

    /**
     * Returns whether the class is able to return metadata for the given value.
     *
     * @param mixed $value Some value
     *
     * @return MetadataInterface Whether metadata can be returned for that value
     */
    public function getMetadataFor($value): MetadataInterface
    {
        if (is_object($value)) {
            $value = get_class($value);
        }

        if (!is_string($value)) {
            throw new NoSuchMetadataException('No metadata for type ' . gettype($value));
        }

        if (!isset($this->metadatas[$value])) {
            throw new NoSuchMetadataException('No metadata for "' . $value . '"');
        }

        return $this->metadatas[$value];
    }

    /**
     * Returns whether the class is able to return metadata for the given value.
     *
     * @param mixed $value Some value
     *
     * @return bool Whether metadata can be returned for that value
     */
    public function hasMetadataFor($value): bool
    {
        if (is_object($value)) {
            $value = get_class($value);
        }

        if (!is_string($value)) {
            return false;
        }

        return isset($this->metadatas[$value]);
    }

    public function addMetadata(ClassMetadata $classMetadata): void
    {
        $this->metadatas[$classMetadata->getClassName()] = $classMetadata;
    }
}
