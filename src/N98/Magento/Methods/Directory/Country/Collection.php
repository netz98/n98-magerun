<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Directory\Country;

use Mage;
use Mage_Directory_Model_Resource_Country_Collection;
use N98\Magento\Methods\ResourceModelInterface;
use RuntimeException;

/**
 * Class Collection
 *
 * @package N98\Magento\Methods\Directory\Country
 */
class Collection implements ResourceModelInterface
{
    /**
     * @return Mage_Directory_Model_Resource_Country_Collection
     */
    public static function getResourceModel(): Mage_Directory_Model_Resource_Country_Collection
    {
        $model = Mage::getResourceModel('directory/country_collection');
        if (!$model instanceof Mage_Directory_Model_Resource_Country_Collection) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
