<?php

declare(strict_types=1);

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
     * @param ResultCollection $results
     * @param Mage_Core_Model_Website $website
     *
     * @return void
     */
    public function check(ResultCollection $results, Mage_Core_Model_Website $website): void;
}
