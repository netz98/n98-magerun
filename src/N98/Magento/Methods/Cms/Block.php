<?php

declare(strict_types=1);

namespace N98\Magento\Methods\Cms;

use Mage;
use Mage_Cms_Model_Block;
use N98\Magento\Methods\ModelInterface;
use RuntimeException;

/**
 * Class Block
 *
 * @package N98\Magento\Methods\Cms
 */
class Block implements ModelInterface
{
    /**
     * Get an instance of cms/block
     *
     * @return Mage_Cms_Model_Block
     */
    public static function getModel(): Mage_Cms_Model_Block
    {
        $model = Mage::getModel('cms/block');
        if (!$model instanceof Mage_Cms_Model_Block) {
            throw new RuntimeException(__METHOD__);
        }
        return $model;
    }
}
