<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\Settings;

use Mage_Core_Model_Store;
use N98\Magento\Command\System\Check\Result;

/**
 * Class BaseUrlCheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
abstract class BaseUrlCheckAbstract extends CheckAbstract
{
    protected $class = 'abstract';

    public function initConfigPaths()
    {
        $this->registerStoreConfigPath('baseUrl', 'web/' . $this->class . '/base_url');
    }

    /**
     * @param Result $result
     * @param Mage_Core_Model_Store $store
     * @param string $baseUrl setting
     */
    protected function checkSettings(Result $result, Mage_Core_Model_Store $store, $baseUrl)
    {
        $errorMessage = 'Wrong hostname configured. <info>Hostname must contain a dot</info>';

        $host = parse_url($baseUrl, PHP_URL_HOST);
        $isValid = (bool) strstr($host, '.');
        $result->setStatus($isValid);
        if ($isValid) {
            $result->setMessage(
                '<info>' . ucfirst($this->class) . ' BaseURL: <comment>' . $baseUrl . '</comment> of Store: <comment>' .
                $store->getCode() . '</comment> - OK'
            );
        } else {
            $result->setMessage(
                '<error>Invalid ' . ucfirst($this->class) . ' BaseURL: <comment>' . $baseUrl .
                '</comment> of Store: <comment>' . $store->getCode() . '</comment> ' . $errorMessage . '</error>'
            );
        }
    }
}
