<?php

declare(strict_types=1);

namespace N98\Util;

use RuntimeException;

/**
 * Class Exec
 *
 * @package N98\Util
 */
class Exec
{
    /**
     * @var string
     */
    public const REDIRECT_STDERR_TO_STDOUT = ' 2>&1';

    /**
     * @var int (0-255)
     */
    public const CODE_CLEAN_EXIT = 0;

    /**
     * Every error in a pipe will be exited with an error code
     */
    public const SET_O_PIPEFAIL = 'set -o pipefail;';

    public static function run(string $command, ?string &$output = null, ?int &$returnCode = null): void
    {
        if (!self::allowed()) {
            $message = sprintf("No PHP exec(), can not execute command '%s'.", $command);
            throw new RuntimeException($message);
        }

        if (OperatingSystem::isBashCompatibleShell() && self::isPipefailOptionAvailable()) {
            $command = self::SET_O_PIPEFAIL . $command;
        }

        $command .= self::REDIRECT_STDERR_TO_STDOUT;

        exec($command, $outputArray, $returnCode);
        $output = self::parseCommandOutput($outputArray);

        if ($returnCode !== self::CODE_CLEAN_EXIT) {
            throw new RuntimeException(
                sprintf('Exit status %d for command %s. Output was: %s', $returnCode, $command, $output),
            );
        }
    }

    /**
     * Exec class is allowed to run
     */
    public static function allowed(): bool
    {
        return function_exists('exec');
    }

    /**
     * string from array of strings representing one line per entry
     * @param list<string> $commandOutput
     */
    private static function parseCommandOutput(array $commandOutput): string
    {
        return implode(PHP_EOL, $commandOutput) . PHP_EOL;
    }

    private static function isPipefailOptionAvailable(): bool
    {
        exec('set -o | grep pipefail 2>&1', $output, $returnCode);
        return $returnCode === self::CODE_CLEAN_EXIT;
    }
}
