<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Mage_Cms_Model_Block;
use N98\Magento\Command\AbstractMagentoCommand;

/**
 * @package N98\Magento\Command\Cms\Block
 */
class AbstractCmsBlockCommand extends AbstractMagentoCommand
{
    /**
     * Get an instance of cms/block
     *
     * @return Mage_Cms_Model_Block
     */
    protected function _getBlockModel(): Mage_Cms_Model_Block
    {
        $model = $this->_getModel('cms/block');
        if (!$model instanceof Mage_Cms_Model_Block) {
            throw new UnexpectedValueException($model, Mage_Cms_Model_Block::class);
        }
        return $model;
    }
}
