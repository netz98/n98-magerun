<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Mage;
use Mage_Core_Helper_Abstract;
use Mage_Core_Helper_Data;
use Mage_Core_Model_Abstract;
use Mage_Core_Model_App;
use Mage_Core_Model_Config;
use Mage_Core_Model_Store;
use Mage_Core_Model_Store_Exception;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\Console\Helper\ParameterHelper;
use N98\Util\Console\Helper\TableHelper;
use N98\Util\Console\Helper\TwigHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * Class AbstractCommandHelper
 *
 * @package N98\Magento\Command
 */
abstract class AbstractCommandHelper extends Command
{
    /**
     * @return string
     */
    protected function getInstalledVersion(): string
    {
        if (method_exists('Mage', 'getOpenMageVersion')) {
            return 'OpenMage LTS ' . Mage::getOpenMageVersion();
        }

        return 'Magento CE ' . Mage::getVersion();
    }

    /**
     * @return DatabaseHelper
     */
    public function getDatabaseHelper(): DatabaseHelper
    {
        return $this->getHelper('database');
    }

    /**
     * @return ParameterHelper
     */
    public function getParameterHelper(): ParameterHelper
    {
        return $this->getHelper('parameter');
    }

    /**
     * @return QuestionHelper
     */
    public function getQuestionHelper(): QuestionHelper
    {
        return $this->getHelper('question');
    }

    /**
     * @return TableHelper
     */
    public function getTableHelper(): TableHelper
    {
        return $this->getHelper('table');
    }

    /**
     * @return TwigHelper
     */
    public function getTwigHelper(): TwigHelper
    {
        return $this->getHelper('twig');
    }

    /**
     * @return Mage_Core_Model_App
     */
    protected function _getMage(): Mage_Core_Model_App
    {
        return Mage::app();
    }

    /**
     * Helper for PhpStan to avoid
     * "Cannot call method loadModulesConfiguration() on Mage_Core_Model_Config|null"
     *
     * @return Mage_Core_Model_Config
     */
    protected function _getMageConfig(): Mage_Core_Model_Config
    {
        return Mage::getConfig();
    }

    /**
     * @return Mage_Core_Model_Store
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getMageStore(): Mage_Core_Model_Store
    {
        return $this->_getMage()->getStore();
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function getCoreHelper(): Mage_Core_Helper_Data
    {
        return Mage::helper('core');
    }

    /**
     * @param string $class class code
     * @return Mage_Core_Model_Abstract
     * @deprecated since v3.0.0
     */
    protected function _getModel(string $class)
    {
        return Mage::getModel($class);
    }

    /**
     * @param string $class class code
     * @return Mage_Core_Helper_Abstract
     * @deprecated since v3.0.0
     */
    protected function _getHelper(string $class)
    {
        return Mage::helper($class);
    }

    /**
     * @param string $class class code
     * @return Mage_Core_Model_Abstract
     * @deprecated since v3.0.0
     */
    protected function _getSingleton(string $class)
    {
        return Mage::getModel($class);
    }

    /**
     * @param string $class class code
     * @return Mage_Core_Model_Abstract
     * @deprecated since v3.0.0
     */
    protected function _getResourceModel(string $class)
    {
        return Mage::getResourceModel($class);
    }

    /**
     * @param string $class class code
     * @return Mage_Core_Model_Abstract
     * @deprecated since v3.0.0
     */
    protected function _getResourceSingleton(string $class)
    {
        return Mage::getResourceSingleton($class);
    }
}
