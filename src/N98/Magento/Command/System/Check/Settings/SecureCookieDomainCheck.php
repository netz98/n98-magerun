<?php

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\StoreCheck;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;

class SecureCookieDomainCheck implements StoreCheck
{
    /**
     * @param ResultCollection $results
     * @param \Mage_Core_Model_Store $store
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();
        $errorMessage = 'Cookie Domain and Secure BaseURL (http) does not match';

        $cookieDomain = \Mage::getStoreConfig('web/cookie/cookie_domain', $store);

        if (!empty($cookieDomain)) {
            $isValid = strpos(parse_url($cookieDomain, PHP_URL_HOST), $cookieDomain);
            $result->setStatus($isValid ? Result::STATUS_OK : Result::STATUS_ERROR);

            if ($result->isValid()) {
                $result->setMessage('<info>Cookie Domain (secure) of Store: <comment>' . $store->getCode() . '</comment> OK');
            } else {
                $result->setMessage('<error>Cookie Domain (secure) <comment>Store: ' . $store->getCode() . '</comment> ' . $errorMessage . '</error>');
            }
        } else {
            $result->setMessage('<info>Cookie Domain (secure) of Store: <comment>' . $store->getCode() . '</comment> OK - No domain set');
        }
    }
}