<?php

namespace N98\Util;

use RuntimeException;

/**
 * Class Exec
 * @package N98\Util
 */
class Exec {

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
     * @param string $commandOutput
     * @param int $returnCode
     */
    public static function run($command, &$commandOutput = null, &$returnCode = null) {

        $command = $command . self::REDIRECT_STDERR_TO_STDOUT;

        exec($command, $commandOutput, $returnCode);
        $commandOutput = self::parseCommandOutput($commandOutput);

        if ($returnCode !== self::CODE_CLEAN_EXIT) {
            throw new RuntimeException($commandOutput);
        }
    }

    /**
     * Exec class is allowed to run
     *
     * @return bool
     */
    public static function allowed() {

        return function_exists('exec');
    }

    /**
     * @param $commandOutput
     * @return string
     */
    private static function parseCommandOutput($commandOutput) {

        return implode(PHP_EOL, $commandOutput);
    }
}
