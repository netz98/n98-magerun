<?php

namespace N98\Util\Validator;

use Symfony\Component\Validator\MetadataFactoryInterface;
use Symfony\Component\Validator\Exception\NoSuchMetadataException;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class FakeMetadataFactory implements MetadataFactoryInterface
{
    protected $metadatas = array();

    public function getMetadataFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!is_string($class)) {
            throw new NoSuchMetadataException('No metadata for type ' . gettype($class));
        }

        if (!isset($this->metadatas[$class])) {
            throw new NoSuchMetadataException('No metadata for "' . $class . '"');
        }

        return $this->metadatas[$class];
    }

    public function hasMetadataFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!is_string($class)) {
            return false;
        }

        return isset($this->metadatas[$class]);
    }

    public function addMetadata(ClassMetadata $metadata)
    {
        $this->metadatas[$metadata->getClassName()] = $metadata;
    }
}
