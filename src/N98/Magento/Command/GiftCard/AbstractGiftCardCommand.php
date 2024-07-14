<?php

namespace N98\Magento\Command\GiftCard;

use N98\Magento\Command\AbstractMagentoCommand;

/**
 * Class AbstractGiftCardCommand
 *
 * @package N98\Magento\Command\GiftCard
 */
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
