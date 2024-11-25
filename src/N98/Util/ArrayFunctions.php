<?php

declare(strict_types=1);

namespace N98\Util;

/**
 * Class Twig
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class ArrayFunctions
{
    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @see http://packages.zendframework.com/docs/latest/manual/en/index.html#zend-stdlib
     */
    public static function mergeArrays(array $a, array $b): array
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
     * @param string $key key to filter
     * @param mixed $value to compare against (strict comparison)
     */
    public static function matrixFilterByValue(array $matrix, string $key, $value): array
    {
        return self::matrixCallbackFilter($matrix, function (array $item) use ($key, $value) {
            return $item[$key] !== $value;
        });
    }

    /**
     * @param string $key to filter
     * @param string $value to compare against
     */
    public static function matrixFilterStartsWith(array $matrix, string $key, string $value): array
    {
        return self::matrixCallbackFilter($matrix, function (array $item) use ($key, $value) {
            return strncmp($item[$key], $value, strlen($value));
        });
    }

    /**
     * @param callable $callback that when return true on the row will unset it
     */
    private static function matrixCallbackFilter(array $matrix, callable $callback): array
    {
        foreach ($matrix as $k => $item) {
            if ($callback($item)) {
                unset($matrix[$k]);
            }
        }

        return $matrix;
    }

    /**
     * Table with ordered columns
     *
     * @param string[] $columns
     */
    public static function columnOrderArrayTable(array $columns, array $table): array
    {
        $closure = function (array $array) use ($columns) {
            return self::columnOrder($columns, $array);
        };
        return array_map($closure, $table);
    }

    /**
     * order array entries (named and numbered) of array by the columns given as string keys.
     *
     * non-existent columns default to numbered entries or if no numbered entries exists any longer, to null.
     *
     * entries in array that could not consume any column are put after the columns.
     *
     * @param string[] $columns
     */
    public static function columnOrder(array $columns, array $array): array
    {
        if ($columns === []) {
            return $array;
        }

        $keys = array_fill_keys($columns, null);
        $keyed = array_intersect_key($array, $keys);

        $arrayLeftover = array_diff_key($array, $keyed);
        $keysLeftover = array_diff_key($keys, $keyed);

        $target = [];
        if ($keysLeftover !== []) {
            foreach ($arrayLeftover as $key => $value) {
                if (is_string($key)) {
                    continue;
                }

                $target[key($keysLeftover)] = $value;
                unset($arrayLeftover[$key]);
                next($keysLeftover);
                if (null === key($keysLeftover)) {
                    break;
                }
            }
        }

        return array_merge($keys, $keyed, $keysLeftover, $target, $arrayLeftover);
    }
}
