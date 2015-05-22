<?php

namespace N98\Util;

/**
 * Class Exec
 * @package N98\Util
 */
class Exec {

    /**
     * @var string
     */
    const REDIRECT_STDERR_TO_STDOUT = ' 2>&1';

    const CODE_CLEAN_EXIT = 0;

    /**
     * @param $command
     * @param null $commandOutput
     * @param null $returnCode
     */
    public static function run ($command, &$commandOutput = null, &$returnCode = null) {

        $command = $command . self::REDIRECT_STDERR_TO_STDOUT;

        exec($command, $commandOutput, $returnCode);
        $commandOutput = self::parseCommandOutput($commandOutput);

        if($returnCode !== self::CODE_CLEAN_EXIT) {
            throw new \RuntimeException($commandOutput);
        }
    }

    /**
     * @param $commandOutput
     * @return string
     */
    protected static function parseCommandOutput($commandOutput) {
        return implode(PHP_EOL, $commandOutput);
    }
}
