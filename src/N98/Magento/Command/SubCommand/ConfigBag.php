<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

use ArrayObject;

/**
 * Class ConfigBag
 *
 * @package N98\Magento\Command\SubCommand
 */
class ConfigBag extends ArrayObject
{
    /**
     * @return $this
     */
    public function setBool(string $key, bool $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function setInt(string $key, int $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function setString(string $key, string $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function setFloat(string $key, float $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function setArray(string $key, array $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function setObject(string $key, object $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    public function getBool(string $key): bool
    {
        return (bool) $this->offsetGet($key);
    }

    public function getInt(string $key): int
    {
        return (int) $this->offsetGet($key);
    }

    public function getString(string $key): string
    {
        return (string) $this->offsetGet($key);
    }

    public function getFloat(string $key): float
    {
        return (float) $this->offsetGet($key);
    }

    public function getArray(string $key): array
    {
        return (array) $this->offsetGet($key);
    }

    public function getObject(string $key): object
    {
        return $this->offsetGet($key);
    }
}
