<?php

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractConfigCommand extends AbstractMagentoCommand
{
    /**
     * @var array strings of configuration scopes
     */
    protected $_scopes = array(
        'default',
        'websites',
        'stores',
    );

    /**
     * @return \Mage_Core_Model_Encryption
     */
    protected function getEncryptionModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            // @TODO Magento 2 support
        } else {
            return \Mage::helper('core')->getEncryptor();
        }
    }

    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getConfigDataModel()
    {
        return $this->_getModel('core/config_data', 'Mage_Core_Model_Config_Data');
    }

    /**
     * @param string $value
     * @param string $encryptionType
     * @return string
     */
    protected function _formatValue($value, $encryptionType)
    {
        if ($encryptionType == 'encrypt') {
            $value = $this->getEncryptionModel()->encrypt($value);
        } elseif ($encryptionType == 'decrypt') {
            $value = $this->getEncryptionModel()->decrypt($value);
        }

        return $value;
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    protected function _validateScopeParam($scope)
    {
        if (!in_array($scope, $this->_scopes)) {
            throw new InvalidArgumentException(
                sprintf('Invalid scope parameter, must be one of: %s.', implode(', ', $this->_scopes))
            );
        }

        return $scope;
    }

    /**
     * @param string $scope
     * @param string $scopeId
     *
     * @return string non-negative integer number
     */
    protected function _convertScopeIdParam($scope, $scopeId)
    {
        if ($scope === 'default') {
            if ("$scopeId" !== "0") {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope ID %d in scope '%s', must be 0", $scopeId, $scope)
                );
            }

            return $scopeId;
        }

        if ($scope == 'websites' && !is_numeric($scopeId)) {
            $website = \Mage::app()->getWebsite($scopeId);
            if (!$website) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter, website '%s' does not exist.", $scopeId)
                );
            }

            return $website->getId();
        }

        if ($scope == 'stores' && !is_numeric($scopeId)) {
            $store = \Mage::app()->getStore($scopeId);
            if (!$store) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter. store '%s' does not exist.", $scopeId)
                );
            }

            return $store->getId();
        }

        if ($scopeId !== (string)(int)$scopeId) {
            throw new InvalidArgumentException(
                sprintf("Invalid scope parameter, %s is not an integer value", var_export($scopeId, true))
            );
        }

        if (0 >= (int)$scopeId) {
            throw new InvalidArgumentException(
                sprintf("Invalid scope parameter, %s is not a positive integer value", var_export($scopeId, true))
            );
        }

        return $scopeId;
    }

    /**
     * @return \Mage_Core_Model_Config
     */
    protected function _getConfigModel()
    {
        return $this->_getModel('core/config', 'Mage_Core_Model_Config');
    }
}
