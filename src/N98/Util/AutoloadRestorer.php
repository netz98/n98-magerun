<?php

declare(strict_types=1);

namespace N98\Util;

/**
 * Utility class to snapshot a set of autoloaders and restore any of the snapshot if removed.
 *
 * Based on SPL autoloader.
 *
 * @package N98\Util
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class AutoloadRestorer
{
    /**
     * @var array
     */
    private $snapshot;

    public function __construct()
    {
        $this->snapshot = spl_autoload_functions();
    }

    /**
     * restore all autoload callbacks that have been unregistered
     */
    public function restore()
    {
        $unregisteredLoaders = $this->getUnregisteredLoaders();

        foreach ($unregisteredLoaders as $unregisteredLoader) {
            spl_autoload_register($unregisteredLoader);
        }
    }

    private function getUnregisteredLoaders()
    {
        $unregistered = [];
        $current = spl_autoload_functions();
        foreach ($this->snapshot as $callback) {
            if (in_array($callback, $current, true)) {
                continue;
            }

            $unregistered[] = $callback;
        }

        return $unregistered;
    }
}
