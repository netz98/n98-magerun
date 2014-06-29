<?php

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\StoreCheck;

class SecureBaseUrlCheck implements StoreCheck
{
    /**
     * @param ResultCollection $results
     * @param \Mage_Core_Model_Store $store
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();
        $errorMessage = 'Wrong hostname configured. <info>Hostname must contain a dot</info>';

        $configValue = \Mage::getStoreConfig('web/secure/base_url', $store);
        $host = parse_url($configValue, PHP_URL_HOST);;
        $isValid = strstr($host, '.');
        $result->setStatus($isValid ? Result::STATUS_OK : Result::STATUS_ERROR);
        if (!$isValid) {
            $result->setMessage('<error>Invalid Secure BaseURL <comment>Store: ' . $store->getCode() . '</comment> ' . $errorMessage . '</error>');
        } else {
            $result->setMessage('<info>Secure BaseURL of Store: <comment>' . $store->getCode() . '</comment> OK');
        }
    }
}