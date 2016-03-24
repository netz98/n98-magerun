<?php

namespace N98\Util;

use RuntimeException;

/**
 * Class Exec
 * @package N98\Util
 */
class Exec
{
    /**
     * @var string
     */
    const REDIRECT_STDERR_TO_STDOUT = ' 2>&1';

    /**
     * @var int (0-255)
     */
    const CODE_CLEAN_EXIT = 0;

    /**
     * @param string $command
     * @param string $output
     * @param int $returnCode
     */
    public static function run($command, &$output = null, &$returnCode = null)
    {
        if (!self::allowed()) {
            $message = sprintf("No PHP exec(), can not execute command '%s'.", $command);
            throw new RuntimeException($message);
        }

        $command = $command . self::REDIRECT_STDERR_TO_STDOUT;

        exec($command, $outputArray, $returnCode);
        $output = self::parseCommandOutput($outputArray);

        if ($returnCode !== self::CODE_CLEAN_EXIT) {
            throw new RuntimeException($output);
        }
    }

    /**
     * Exec class is allowed to run
     *
     * @return bool
     */
    public static function allowed()
    {
        return function_exists('exec');
    }

    /**
     * string from array of strings representing one line per entry
     *
     * @param array $commandOutput
     * @return string
     */
    private static function parseCommandOutput(array $commandOutput)
    {
        return implode(PHP_EOL, $commandOutput) . PHP_EOL;
    }
}
