<?php

declare(strict_types=1);

namespace N98\Util;

use BadMethodCallException;
use Closure;

/**
 * Autoloader with self-registration, de-registration, muting and implementation switching
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
final class AutoloadHandler
{
    /**
     * Throw exception if the autoload implementation is not callable (default). If no exception is thrown,
     * autoload callback is just ignored
     */
    public const NO_EXCEPTION = 1;

    public const NO_AUTO_REGISTER = 2;

    private ?int $flags;

    /**
     * @var callable|null
     */
    private $callback;

    private bool $splRegistered = false;

    private bool $enabled;

    public static function create(?callable $callback, ?int $flags = null): AutoloadHandler
    {
        return new self($callback, $flags);
    }

    public function __construct(?callable $callback, ?int $flags = null)
    {
        if (null === $flags) {
            $flags = 0;
        }

        $this->flags = $flags;
        $this->enabled = true;
        $this->callback = $callback;
        $this->flags & self::NO_AUTO_REGISTER || $this->register();
    }

    public function register(): void
    {
        spl_autoload_register($this);
        $this->splRegistered = true;
    }

    public function unregister(): void
    {
        spl_autoload_unregister($this);
        $this->splRegistered = false;
    }

    /**
     * @return false|mixed
     */
    public function __invoke(string $className)
    {
        if (!$this->splRegistered) {
            return false;
        }

        if (!$this->enabled) {
            return false;
        }

        if (!is_callable($this->callback)) {
            if (($this->flags & self::NO_EXCEPTION) !== 0) {
                return false;
            }

            throw new BadMethodCallException('Autoload callback is not callable');
        }

        return call_user_func($this->callback, $className);
    }

    public function getCleanupCallback(): Closure
    {
        $self = (object) ['ref' => $this];

        return function () use ($self): void {
            if (isset($self->ref)) {
                $self->ref->reset();
                unset($self->ref);
            }
        };
    }

    /**
     * Unregister from SPL Stack and destroy callback reference.
     */
    public function reset(): void
    {
        $this->unregister();
        $this->callback = null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $flagEnabled): void
    {
        $this->enabled = $flagEnabled;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function setCallback(?callable $callback): void
    {
        $this->callback = $callback;
    }
}
