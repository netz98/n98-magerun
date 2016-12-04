<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\Settings;

use Mage;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\StoreCheck;

/**
 * Class CheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
abstract class CheckAbstract implements StoreCheck
{
    private $storeConfigPaths = array();

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
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();

        $typedParams = array(
            'result' => $result,
            'store'  => $store,
        );

        $paramValues = $this->getParamValues($store, $typedParams);

        $name = 'checkSettings';
        $method = new \ReflectionMethod($this, $name);
        $parameters = $method->getParameters();

        $arguments = array();
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
            $paramValues += array($paramName => null);
            $arguments[] = $paramValues[$paramName];
        }

        call_user_func_array(array($this, $name), $arguments);
    }

    /**
     * @param \Mage_Core_Model_Store $store
     * @param array                  $typedParams
     *
     * @return array
     */
    private function getParamValues(\Mage_Core_Model_Store $store, array $typedParams)
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
