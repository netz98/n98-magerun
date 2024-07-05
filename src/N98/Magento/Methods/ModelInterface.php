<?php

declare(strict_types=1);

namespace N98\Magento\Methods;

use Mage_Core_Model_Abstract;

/**
 * Interface ModelInterface
 *
 * @package N98\Magento\Methods
 */
interface ModelInterface
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    public static function getModel(): Mage_Core_Model_Abstract;
}
