<?php

declare(strict_types=1);

namespace N98\Util;

use Symfony\Component\Process\Process;

/**
 * Utility class handling arguments building in use with Symfony\Process
 *
 * @see Process
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class ProcessArguments
{
    private array $arguments;

    public static function create(array $arguments = []): ProcessArguments
    {
        return new self($arguments);
    }

    /**
     * ProcessArguments constructor.
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * @return $this
     */
    public function addArg(string $argument)
    {
        $this->arguments[] = $argument;
        return $this;
    }

    /**
     * @param string $separator [optional]
     * @param string $prefix [optional]
     */
    public function addArgs(array $arguments, string $separator = '=', string $prefix = '--'): ProcessArguments
    {
        foreach ($arguments as $key => $value) {
            $this->addArg(
                $this->conditional($key, $value, $separator, $prefix),
            );
        }

        return $this;
    }

    /**
     * @param string|true $value
     */
    private function conditional(string $key, $value, string $separator = '=', string $prefix = '--'): string
    {
        if ($key !== '' && $key !== '0') {
            return $this->conditionalPrefix($key, $prefix) . $this->conditionalValue($value, $separator);
        }

        return (string) $value;
    }

    private function conditionalPrefix(string $arg, string $prefix = '--'): string
    {
        if ('-' === $arg[0]) {
            return $arg;
        }

        return $prefix . $arg;
    }

    /**
     * @param string|true $value
     */
    private function conditionalValue($value, string $separator = '='): string
    {
        if ($value === true) {
            return '';
        }

        return $separator . $value;
    }

    public function createProcess(): Process
    {
        return new Process($this->arguments);
    }
}
