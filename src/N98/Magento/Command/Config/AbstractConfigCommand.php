<?php

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractConfigCommand extends AbstractMagentoCommand
{
    const DISPLAY_NULL_UNKNOWN_VALUE = "NULL (NULL/\"unknown\" value)";

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
        if ($value === null) {
            $formatted = $value;
        } elseif ($encryptionType === 'encrypt') {
            $formatted = $this->getEncryptionModel()->encrypt($value);
        } elseif ($encryptionType === 'decrypt') {
            $formatted = $this->getEncryptionModel()->decrypt($value);
        } else {
            $formatted = $value;
        }

        return $formatted;
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
     * @param boolean $allowZeroScope
     *
     * @return string non-negative integer number
     */
    protected function _convertScopeIdParam($scope, $scopeId, $allowZeroScope = false)
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

        $this->invalidScopeId(
            $scopeId !== (string) (int) $scopeId,
            "Invalid scope parameter, %s is not an integer value",
            $scopeId
        );

        $this->invalidScopeId(
            0 - (bool) $allowZeroScope >= (int) $scopeId,
            "Invalid scope parameter, %s is not a positive integer value",
            $scopeId
        );

        return $scopeId;
    }

    /**
     * @param boolean $condition
     * @param string $mask
     * @param string $scopeId
     */
    private function invalidScopeId($condition, $mask, $scopeId)
    {
        if (!$condition) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf($mask, var_export($scopeId, true))
        );
    }

    /**
     * @return \Mage_Core_Model_Config
     */
    protected function _getConfigModel()
    {
        return $this->_getModel('core/config', 'Mage_Core_Model_Config');
    }
}
