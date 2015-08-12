<?php

namespace N98\Magento\Command\System\Check;

/**
 * Interface StoreCheck
 *
 * @package N98\Magento\Command\System\Check
 */
interface StoreCheck
{
    /**
     * @param ResultCollection       $results
     * @param \Mage_Core_Model_Store $store
     *
     * @return
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store);
}
