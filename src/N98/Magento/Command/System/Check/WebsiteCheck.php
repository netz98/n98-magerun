<?php

namespace N98\Magento\Command\System\Check;

/**
 * Interface WebsiteCheck
 *
 * @package N98\Magento\Command\System\Check
 */
interface WebsiteCheck
{
    /**
     * @param ResultCollection         $results
     * @param \Mage_Core_Model_Website $website
     *
     * @return
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Website $website);
}
