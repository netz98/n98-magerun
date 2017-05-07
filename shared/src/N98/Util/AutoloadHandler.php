<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util;

/**
 * Autloader with self-registration, de-registration, muting and implementation switching
 *
 * @package N98\Util
 */
final class AutoloadHandler
{
    /**
     * Throw exception if the autoload implementation is not callable (default). If no exception is thrown,
     * autoload callback is just ignored
     */
    const NO_EXCEPTION = 1;

    /**
     *
     */
    const NO_AUTO_REGISTER = 2;

    /**
     * @var integer
     */
    private $flags;

    private $callback;

    private $splRegistered;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param $callback
     * @param integer $flags [optional]
     * @return AutoloadHandler
     */
    public static function create($callback, $flags = null)
    {
        return new self($callback, $flags);
    }

    /**
     * AutoloadHandler constructor.
     *
     * @param $callback
     * @param integer $flags [optional]
     */
    public function __construct($callback, $flags = null)
    {
        if (null === $flags) {
            $flags = 0;
        }

        $this->flags = $flags;
        $this->enabled = true;
        $this->callback = $callback;
        $this->flags & self::NO_AUTO_REGISTER || $this->register();
    }

    public function register()
    {
        spl_autoload_register($this);
        $this->splRegistered = true;
    }

    public function unregister()
    {
        spl_autoload_unregister($this);
        $this->splRegistered = false;
    }

    public function __invoke($className)
    {
        if (!$this->splRegistered) {
            return false;
        }

        if (!$this->enabled) {
            return false;
        }

        if (!is_callable($this->callback)) {
            if ($this->flags & self::NO_EXCEPTION) {
                return false;
            }
            throw new \BadMethodCallException('Autoload callback is not callable');
        }

        return call_user_func($this->callback, $className);
    }

    public function getCleanupCallback()
    {
        $self = (object) array('ref' => $this);

        return function () use ($self) {
            if (isset($self->ref)) {
                $self->ref->reset();
                unset($self->ref);
            }
        };
    }

    /**
     * Unregister from SPL Stack and destroy callback reference.
     */
    public function reset()
    {
        $this->unregister();
        $this->callback = null;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $flagEnabled
     */
    public function setEnabled($flagEnabled)
    {
        $this->enabled = (bool) $flagEnabled;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * @param mixed $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }
}
