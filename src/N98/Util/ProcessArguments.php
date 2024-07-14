<?php

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
    /**
     * @var array
     */
    private $arguments;

    /**
     * @param array $arguments
     * @return \N98\Util\ProcessArguments
     */
    public static function create(array $arguments = [])
    {
        return new self($arguments);
    }

    /**
     * ProcessArguments constructor.
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * @param $argument
     * @return $this
     */
    public function addArg($argument)
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * @param array $arguments
     * @param string $separator [optional]
     * @param string $prefix [optional]
     * @return $this
     */
    public function addArgs(array $arguments, $separator = '=', $prefix = '--')
    {
        foreach ($arguments as $key => $value) {
            $this->addArg(
                $this->conditional($key, $value, $separator, $prefix)
            );
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string|true $value
     * @param string $separator
     * @param string $prefix
     * @return string
     */
    private function conditional($key, $value, $separator = '=', $prefix = '--')
    {
        $buffer = (string) $value;

        if (is_string($key) && !empty($key)) {
            $buffer = $this->conditionalPrefix($key, $prefix) . $this->conditionalValue($value, $separator);
        }

        return $buffer;
    }

    /**
     * @param string $arg
     * @param string $prefix
     * @return string
     */
    private function conditionalPrefix($arg, $prefix = '--')
    {
        if ('-' === $arg[0]) {
            return $arg;
        }

        return "$prefix$arg";
    }

    /**
     * @param string|true $value
     * @param string $separator
     * @return string
     */
    private function conditionalValue($value, $separator = '=')
    {
        if ($value === true) {
            return '';
        }

        return $separator . $value;
    }

    /**
     * @return Process
     */
    public function createProcess()
    {
        return new Process($this->arguments);
    }
}
