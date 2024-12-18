<?php

declare(strict_types=1);

namespace N98;

use Composer\Autoload\ClassLoader;
use ErrorException;
use N98\Magento\Application;

/**
 * Bootstrap class for the Magerun applications (Symfony console based application)
 *
 * @package N98
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class MagerunBootstrap
{
    /**
     * @throws ErrorException
     */
    public static function createApplication(?ClassLoader $classLoader = null): Application
    {
        if (!$classLoader instanceof ClassLoader) {
            $classLoader = self::getLoader();
        }

        return new Application($classLoader);
    }

    /**
     * @throws ErrorException
     */
    public static function getLoader(): ClassLoader
    {
        $projectBasedir = getcwd();
        if (!($loader = self::includeIfExists($projectBasedir . '/vendor/autoload.php')) instanceof ClassLoader
            && !($loader = self::includeIfExists($projectBasedir . '/../../autoload.php')) instanceof ClassLoader
        ) {
            throw new ErrorException(
                'You must set up the project dependencies, run the following commands:' . PHP_EOL .
                'curl -s https://getcomposer.org/installer | php' . PHP_EOL .
                'php composer.phar install' . PHP_EOL,
            );
        }

        return $loader;
    }

    public static function includeIfExists(string $file): ?ClassLoader
    {
        if (file_exists($file)) {
            return include $file;
        }

        return null;
    }
}
