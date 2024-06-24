<?php

declare(strict_types=1);

namespace N98\Magento;

use N98\Util\AutoloadRestorer;
use RuntimeException;

/**
 * Magento initializer (Magento 1)
 *
 * @package N98\Magento
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class Initialiser
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
     * Bootstrap Magento application
     */
    public static function bootstrap($magentoPath)
    {
        $initializer = new Initialiser($magentoPath);
        $initializer->requireMage();
    }

    /**
     * Initializer constructor.
     *
     * @param string $magentoPath
     */
    public function __construct(string  $magentoPath)
    {
        $this->magentoPath = $magentoPath;
    }

    /**
     * Require app/Mage.php if class Mage does not yet exists. Preserves auto-loaders
     *
     * @see \Mage (final class)
     */
    public function requireMage()
    {
        if (class_exists(self::CLASS_MAGE, false)) {
            return;
        }

        $this->requireOnce();

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
        $restorer = new AutoloadRestorer();

        $path = $this->magentoPath . '/' . self::PATH_APP_MAGE_PHP;
        initialiser_require_once($path);

        // Restore autoloader that might be removed by extensions that overwrite Varien/Autoload
        $restorer->restore();
    }
}

/**
 * use require-once inside a function with its own variable scope and no $this (?)
 */
function initialiser_require_once()
{
    require_once func_get_arg(0);
}
