<?php

namespace N98\Magento\Command\System\Check\Settings;

use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\StoreCheck;
use ReflectionMethod;

/**
 * Class CheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
abstract class CheckAbstract implements StoreCheck
{
    private $storeConfigPaths = [];

    final public function __construct()
    {
        $this->initConfigPaths();
    }

    abstract protected function initConfigPaths();

    /**
     * @param string $name
     * @param string $configPath
     */
    protected function registerStoreConfigPath($name, $configPath)
    {
        $this->storeConfigPaths[$name] = $configPath;
    }

    /**
     * @param ResultCollection       $results
     * @param \Mage_Core_Model_Store $store
     *
     */
    public function check(ResultCollection $results, Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();

        $typedParams = ['result' => $result, 'store'  => $store];

        $paramValues = $this->getParamValues($store, $typedParams);

        $name = 'checkSettings';
        $method = new ReflectionMethod($this, $name);
        $parameters = $method->getParameters();

        $arguments = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $paramClass = $parameter->getClass();

            // create named parameter from type-hint if applicable
            if ($paramClass) {
                foreach ($typedParams as $object) {
                    if ($paramClass->isSubclassOf(get_class($object))) {
                        $paramValues[$paramName] = $object;
                        break;
                    }
                }
            }

            // use named parameter, otherwise null
            $paramValues += [$paramName => null];
            $arguments[] = $paramValues[$paramName];
        }

        call_user_func_array([$this, $name], $arguments);
    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param array                  $typedParams
     *
     * @return array
     */
    private function getParamValues(Mage_Core_Model_Store $store, array $typedParams)
    {
        $paramValues = $this->storeConfigPaths;

        foreach ($paramValues as $name => $path) {
            $value = Mage::getStoreConfig($path, $store);
            $paramValues[$name] = $value;
        }

        $paramValues = $typedParams + $paramValues;

        return $paramValues;
    }
}
