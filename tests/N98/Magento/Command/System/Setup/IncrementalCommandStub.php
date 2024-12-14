<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use ReflectionException;

/**
 * Class IncrementalCommandStub
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class IncrementalCommandStub extends IncrementalCommand
{
    /** @noinspection MagicMethodsValidityInspection */
    public function __construct()
    {
        // missing parent constructor call by intention
    }

    /**
     * @param object|string $object
     * @return array|string
     * @throws ReflectionException
     */
    public function callProtectedMethodFromObject(string $method, $object, array $args = [])
    {
        return $this->_callProtectedMethodFromObject($method, $object, $args);
    }
}
