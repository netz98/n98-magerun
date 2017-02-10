<?php
/**
 * Created by PhpStorm.
 * User: mot
 * Date: 13.12.16
 * Time: 00:08
 */

namespace N98\Magento\Command\System\Cron;

use BadMethodCallException;

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
    /**
     * @var array
     */
    private $backup;

    /**
     * @var array
     */
    private $keys;

    public function __construct()
    {
        $this->keys = array('SCRIPT_NAME', 'SCRIPT_FILENAME');
    }

    /**
     *
     */
    public function initalize()
    {
        if (isset($this->backup)) {
            throw new BadMethodCallException('Environment already backed up, can\'t initialize any longer');
        }

        if (!is_array($GLOBALS['argv'])) {
            throw new \UnexpectedValueException('Need argv to work');
        }

        $basename = basename($GLOBALS['argv'][0]);

        foreach ($this->keys as $key) {
            $buffer = $_SERVER[$key];
            $this->backup[$key] = $buffer;
            $_SERVER[$key] = str_replace($basename, 'index.php', $buffer);
        }
    }

    public function reset()
    {
        if (false === isset($this->backup)) {
            throw new BadMethodCallException('Environment not yet backed up, initalize first, can\'t reset');
        }

        foreach ($this->backup as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $this->backup = null;
    }
}
