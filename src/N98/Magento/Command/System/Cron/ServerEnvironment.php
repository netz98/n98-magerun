<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use BadMethodCallException;
use UnexpectedValueException;

/**
 * Class ServerEnvironment
 *
 * Set $_SERVER environment for URL generating while sys:cron:run
 *
 * @see https://github.com/netz98/n98-magerun/issues/871
 * @package N98\Magento\Command\System\Cron
 */
class ServerEnvironment
{
    /**
     * @var array<string, string>|null
     */
    private ?array $backup;

    /**
     * @var array<int, string>
     */
    private array $keys;

    public function __construct()
    {
        $this->keys = ['SCRIPT_NAME', 'SCRIPT_FILENAME'];
    }

    public function initialize(): void
    {
        if (isset($this->backup)) {
            throw new BadMethodCallException('Environment already backed up, can\'t initialize any longer');
        }

        if (!is_array($GLOBALS['argv'])) {
            throw new UnexpectedValueException('Need argv to work');
        }

        $basename = $GLOBALS['argv'][0];
        if (is_string($basename)) {
            $basename = basename($basename);

            foreach ($this->keys as $key) {
                /** @var string $buffer */
                $buffer = $_SERVER[$key];
                $this->backup[$key] = $buffer;
                $_SERVER[$key] = str_replace($basename, 'index.php', $buffer);
            }
        }
    }

    public function reset(): void
    {
        if (false === isset($this->backup)) {
            throw new BadMethodCallException('Environment not yet backed up, initialize first, can\'t reset');
        }

        foreach ($this->backup as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $this->backup = null;
    }
}
