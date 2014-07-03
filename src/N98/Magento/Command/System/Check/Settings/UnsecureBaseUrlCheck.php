<?php

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\StoreCheck;

class UnsecureBaseUrlCheck implements StoreCheck
{
    /**
     * @param ResultCollection $results
     * @param \Mage_Core_Model_Store $store
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();
        $errorMessage = 'Wrong hostname configured. <info>Hostname must contain a dot</info>';

        $configValue = \Mage::getStoreConfig('web/unsecure/base_url', $store);
        $host = parse_url($configValue, PHP_URL_HOST);;
        $isValid = strstr($host, '.');
        $result->setStatus($isValid ? Result::STATUS_OK : Result::STATUS_ERROR);
        if (!$isValid) {
            $result->setMessage('<error>Invalid Unsecure BaseURL <comment>Store: ' . $store->getCode() . '</comment> ' . $errorMessage . '</error>');
        } else {
            $result->setMessage('<info>Unsecure BaseURL of Store: <comment>' . $store->getCode() . '</comment> OK');
        }
    }
}