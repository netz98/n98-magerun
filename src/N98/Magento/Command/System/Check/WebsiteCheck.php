<?php

namespace N98\Magento\Command\System\Check;

use Mage_Core_Model_Website;

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
     */
    public function check(ResultCollection $results, Mage_Core_Model_Website $website);
}
