<?php
/*
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

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
     * @param string[] $columns
     * @param array $table
     * @return array table with ordered columns
     */
    public static function columnOrderArrayTable(array $columns, array $table)
    {
        return array_map(function (array $array) use ($columns) {
            return self::columnOrder($columns, $array);
        }, $table);
    }

    /**
     * order array entries (named and numbered) of array by the columns given as string keys.
     *
     * non-existent columns default to numbered entries or if no numbered entries exists any longer, to null.
     *
     * entries in array that could not consume any column are put after the columns.
     *
     * @param string[] $columns
     * @param array $array
     * @return array
     */
    public static function columnOrder(array $columns, array $array)
    {
        if (!$columns) {
            return $array;
        }

        $keys = array_fill_keys($columns, null);

        $keyed = array_intersect_key($array, $keys);

        $arrayLeftover = array_diff_key($array, $keyed);
        $keysLeftover = array_diff_key($keys, $keyed);

        $target = array();
        if ($keysLeftover) {
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

        $result = array_merge($keys, $keyed, $keysLeftover, $target, $arrayLeftover);

        return $result;
    }
}
