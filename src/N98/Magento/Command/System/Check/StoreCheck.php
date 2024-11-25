<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check;

use Mage_Core_Model_Store;

/**
 * Interface StoreCheck
 *
 * @package N98\Magento\Command\System\Check
 */
interface StoreCheck
{
    public function check(ResultCollection $resultCollection, Mage_Core_Model_Store $mageCoreModelStore): void;
}
