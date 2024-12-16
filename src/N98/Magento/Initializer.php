<?php

declare(strict_types=1);

namespace N98\Magento;

use N98\Util\AutoloadRestorer;
use RuntimeException;

/**
 * Magento initializer (Magento 1)
 *
 * @package N98\Magento
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class Initializer
{
    /**
     * Mage filename
     */
    public const PATH_APP_MAGE_PHP = 'app/Mage.php';

    /**
     * Mage classname
     */
    public const CLASS_MAGE = 'Mage';

    /**
     * @var string path to Magento root directory
     */
    private string $magentoPath;

    /**
     * Initializer constructor.
     */
    public function __construct(string $magentoPath)
    {
        $this->magentoPath = $magentoPath;
    }

    /**
     * Bootstrap Magento application
     */
    public static function bootstrap(string $magentoPath): void
    {
        $initializer = new Initializer($magentoPath);
        $initializer->requireMage();
    }

    /**
     * Require app/Mage.php if class Mage does not yet exists. Preserves auto-loaders
     *
     * @see \Mage (final class)
     */
    public function requireMage(): void
    {
        if (class_exists(self::CLASS_MAGE, false)) {
            return;
        }

        $this->requireOnce();

        // @phpstan-ignore booleanNot.alwaysTrue
        if (!class_exists(self::CLASS_MAGE, false)) {
            throw new RuntimeException(sprintf('Failed to load definition of "%s" class', self::CLASS_MAGE));
        }
    }

    /**
     * Require app/Mage.php in its own scope while preserving all autoloader.
     */
    private function requireOnce(): void
    {
        // Create a new AutoloadRestorer to capture current auto-loaders
        $autoloadRestorer = new AutoloadRestorer();

        $path = $this->magentoPath . '/' . self::PATH_APP_MAGE_PHP;
        initialiser_require_once($path);

        // Restore auto-loaders that might be removed by extensions that overwrite Varien/Autoload
        $autoloadRestorer->restore();
    }
}

/**
 * use require-once inside a function with its own variable scope and no $this (?)
 */
function initialiser_require_once(): void
{
    require_once func_get_arg(0);
}
