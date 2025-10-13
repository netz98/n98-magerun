<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Helper_Data;
use Mage_Core_Model_Config;
use Mage_Core_Model_Config_Data;
use Mage_Core_Model_Encryption;
use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractConfigCommand
 *
 * @package N98\Magento\Command\Config
 */
abstract class AbstractConfigCommand extends AbstractMagentoCommand
{
    public const DISPLAY_NULL_UNKNOWN_VALUE = 'NULL (NULL/"unknown" value)';

    /**
     * @var string[] strings of configuration scopes
     */
    protected array $_scopes = ['default', 'websites', 'stores'];

    protected function getEncryptionModel(): Mage_Core_Model_Encryption
    {
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        return $helper->getEncryptor();
    }

    protected function _getConfigDataModel(): Mage_Core_Model_Config_Data
    {
        /** @var Mage_Core_Model_Config_Data $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('core/config_data');
        return $mageCoreModelAbstract;
    }

    /**
     * @param string|false $encryptionType
     */
    protected function _formatValue(?string $value, $encryptionType): ?string
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

    protected function _validateScopeParam(string $scope): string
    {
        if (!in_array($scope, $this->_scopes)) {
            throw new InvalidArgumentException(
                sprintf('Invalid scope parameter, must be one of: %s.', implode(', ', $this->_scopes)),
            );
        }

        return $scope;
    }

    /**
     * @return string|int|null non-negative integer number
     * @throws Mage_Core_Exception
     */
    protected function _convertScopeIdParam(string $scope, string $scopeId, bool $allowZeroScope = false)
    {
        if ($scope === 'default') {
            if ($scopeId !== '0') {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope ID %d in scope '%s', must be 0", $scopeId, $scope),
                );
            }

            return $scopeId;
        }

        if ($scope === 'websites' && !is_numeric($scopeId)) {
            $website = Mage::app()->getWebsite($scopeId);
            if (!$website) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter, website '%s' does not exist.", $scopeId),
                );
            }

            return $website->getId();
        }

        if ($scope === 'stores' && !is_numeric($scopeId)) {
            $store = Mage::app()->getStore($scopeId);
            if (!$store) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter. store '%s' does not exist.", $scopeId),
                );
            }

            return $store->getId();
        }

        $this->invalidScopeId(
            $scopeId !== (string) (int) $scopeId,
            'Invalid scope parameter, %s is not an integer value',
            $scopeId,
        );

        $this->invalidScopeId(
            0 - $allowZeroScope >= (int) $scopeId,
            'Invalid scope parameter, %s is not a positive integer value',
            $scopeId,
        );

        return $scopeId;
    }

    /**
     * @param mixed $condition
     */
    private function invalidScopeId($condition, string $mask, string $scopeId): void
    {
        if (!$condition) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf($mask, var_export($scopeId, true)),
        );
    }

    protected function _getConfigModel(): Mage_Core_Model_Config
    {
        /** @var Mage_Core_Model_Config $mageCoreModelAbstract */
        $mageCoreModelAbstract = Mage::getModel('core/config');
        return $mageCoreModelAbstract;
    }
}
