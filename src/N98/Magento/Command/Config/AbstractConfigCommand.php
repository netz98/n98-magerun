<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Config;
use Mage_Core_Model_Config_Data;
use Mage_Core_Model_Encryption;
use N98\Magento\Command\AbstractCommand;

abstract class AbstractConfigCommand extends AbstractCommand
{
    public const DISPLAY_NULL_UNKNOWN_VALUE = "NULL (NULL/\"unknown\" value)";

    public const COMMAND_ARGUMENT_PATH = 'path';

    public const COMMAND_OPTION_SCOPE = 'scope';

    public const COMMAND_OPTION_SCOPE_ID = 'scope-id';

    /**
     * @var array<int, string> strings of configuration scopes
     */
    protected array $_scopes = ['default', 'websites', 'stores'];

    /**
     * @return Mage_Core_Model_Encryption
     */
    protected function getEncryptionModel()
    {
        return Mage::helper('core')->getEncryptor();
    }

    /**
     * @return Mage_Core_Model_Config
     */
    protected function _getConfigModel(): Mage_Core_Model_Config
    {
        return Mage::getModel('core/config');
    }

    /**
     * @return Mage_Core_Model_Config_Data
     */
    protected function _getConfigDataModel(): Mage_Core_Model_Config_Data
    {
        return Mage::getModel('core/config_data');
    }

    /**
     * @param string|null $value
     * @param string|null $encryptionType
     * @return string|null
     */
    protected function _formatValue(?string $value, ?string $encryptionType): ?string
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
     * @return string
     */
    protected function _validateScopeParam(string $scope): string
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
     * @param bool $allowZeroScope
     * @return string|int|null non-negative integer number
     * @throws Mage_Core_Exception
     */
    protected function _convertScopeIdParam(string $scope, string $scopeId, bool $allowZeroScope = false)
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
            $website = Mage::app()->getWebsite($scopeId);
            if (!$website) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter, website '%s' does not exist.", $scopeId)
                );
            }

            return $website->getId();
        }

        if ($scope == 'stores' && !is_numeric($scopeId)) {
            $store = Mage::app()->getStore($scopeId);
            if (!$store) {
                throw new InvalidArgumentException(
                    sprintf("Invalid scope parameter. store '%s' does not exist.", $scopeId)
                );
            }

            return $store->getId();
        }

        $this->invalidScopeId(
            (string) $scopeId !== (string) (int) $scopeId,
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
     * @param bool $condition
     * @param string $mask
     * @param string $scopeId
     */
    private function invalidScopeId(bool $condition, string $mask, string $scopeId): void
    {
        if (!$condition) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf($mask, var_export($scopeId, true))
        );
    }
}
