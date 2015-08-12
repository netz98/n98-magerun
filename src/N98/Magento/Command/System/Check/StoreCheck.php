<?php

namespace N98\Magento\Command\System\Check;

interface StoreCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store);
}