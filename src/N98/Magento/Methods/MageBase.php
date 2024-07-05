<?php

declare(strict_types=1);

namespace N98\Magento\Methods;

use Mage;
use Mage_Core_Model_App;
use Mage_Core_Model_Config;
use Mage_Core_Model_Store;
use RuntimeException;

/**
 * Class MageMethods
 *
 * @package N98\Magento
 */
class MageBase
{
    /**
     * @return Mage_Core_Model_App
     */
    public static function app(): Mage_Core_Model_App
    {
        $app = Mage::app();
        if (!$app instanceof Mage_Core_Model_App) {
            throw new RuntimeException(__METHOD__);
        }
        return $app;
    }

    /**
     * @param string $event
     * @param array $data
     *
     * @return Mage_Core_Model_App
     */
    public static function dispatchEvent(string $event, array $data = []): Mage_Core_Model_App
    {
        return Mage::dispatchEvent($event, $data);
    }

    /**
     * Helper for PhpStan to avoid
     * "Cannot call method on Mage_Core_Model_Config|null"
     *
     * @return Mage_Core_Model_Config
     */
    public static function getConfig(): Mage_Core_Model_Config
    {
        $config = Mage::getConfig();
        if (!$config instanceof Mage_Core_Model_Config) {
            throw new RuntimeException(__METHOD__);
        }
        return $config;
    }

    /**
     * @param string $path
     * @param bool|int|Mage_Core_Model_Store|null|string $store
     *
     * @return mixed
     */
    public static function getStoreConfig(string $path, $store = null)
    {
        return Mage::getStoreConfig($path, $store);
    }

    /**
     * @param string $path
     * @param bool|int|Mage_Core_Model_Store|null|string $store
     *
     * @return string
     */
    public static function getStoreConfigAsString(string $path, $store = null): string
    {
        $config = Mage::getStoreConfig($path, $store);
        if (!is_string($config)) {
            throw new RuntimeException(__METHOD__);
        }
        return $config;
    }
}
