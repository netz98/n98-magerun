<?php

declare(strict_types=1);

namespace N98\Magento\Methods;

use Varien_Data_Collection_Db;

/**
 * Interface ResourceModelInterface
 *
 * @package N98\Magento\Methods
 */
interface ResourceModelInterface
{
    /**
     * @return Varien_Data_Collection_Db
     */
    public static function getResourceModel(): Varien_Data_Collection_Db;
}
