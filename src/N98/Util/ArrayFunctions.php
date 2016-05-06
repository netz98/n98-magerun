<?php

namespace N98\Util;

class ArrayFunctions
{
    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @see http://packages.zendframework.com/docs/latest/manual/en/index.html#zend-stdlib
     * @param  array $a
     * @param  array $b
     * @return array
     */
    public static function mergeArrays(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = self::mergeArrays($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * @param array $matrix
     * @param string $key key to filter
     * @param mixed $value to compare against (strict comparison)
     * @return array
     */
    public static function matrixFilterByValue(array $matrix, $key, $value)
    {
        return self::matrixCallbackFilter($matrix, function (array $item) use ($key, $value) {
            return $item[$key] !== $value;
        });
    }

    /**
     * @param array $matrix
     * @param string $key to filter
     * @param string $value to compare against
     * @return array
     */
    public static function matrixFilterStartswith(array $matrix, $key, $value)
    {
        return self::matrixCallbackFilter($matrix, function (array $item) use ($key, $value) {
            return strncmp($item[$key], $value, strlen($value));
        });
    }

    /**
     * @param array $matrix
     * @param callable $callback that when return true on the row will unset it
     * @return array
     */
    private static function matrixCallbackFilter(array $matrix, $callback)
    {
        foreach ($matrix as $k => $item) {
            if ($callback($item)) {
                unset($matrix[$k]);
            }
        }

        return $matrix;
    }
}
