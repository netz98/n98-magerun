<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: mot
 * Date: 13.12.16
 * Time: 00:08
 */

namespace N98\Magento\Command\System\Cron;

use BadMethodCallException;
use UnexpectedValueException;

/**
 * Class ServerEnvironment
 *
 * Set $_SERVER environment for URL generating while sys:cron:run
 *
 * @see https://github.com/netz98/n98-magerun/issues/871
 *
 * @package N98\Magento\Command\System\Cron
 */
class ServerEnvironment
{
    private ?array $backup = null;

    private array $keys = ['SCRIPT_NAME', 'SCRIPT_FILENAME'];

    public function initalize(): void
    {
        if ($this->backup !== null) {
            throw new BadMethodCallException("Environment already backed up, can't initialize any longer");
        }

        if (!is_array($GLOBALS['argv'])) {
            throw new UnexpectedValueException('Need argv to work');
        }

        $basename = basename($GLOBALS['argv'][0]);

        foreach ($this->keys as $key) {
            $buffer = $_SERVER[$key];
            $this->backup[$key] = $buffer;
            $_SERVER[$key] = str_replace($basename, 'index.php', $buffer);
        }
    }

    public function reset(): void
    {
        if ($this->backup === null) {
            throw new BadMethodCallException("Environment not yet backed up, initialize first, can't reset");
        }

        foreach ($this->backup as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $this->backup = null;
    }
}
