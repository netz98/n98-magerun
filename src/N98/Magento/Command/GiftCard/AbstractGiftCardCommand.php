<?php

namespace N98\Magento\Command\GiftCard;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractGiftCardCommand extends AbstractMagentoCommand
{
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }
}
