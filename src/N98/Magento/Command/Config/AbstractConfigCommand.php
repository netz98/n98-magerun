<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractConfigCommand extends AbstractMagentoCommand
{
    /**
     * @return \Mage_Core_Model_Abstract
     */
    protected function getEncryptionModel()
    {
        return $this->_getModel('core/encryption', $mage2ClassName = null)
            ->setHelper($this->getCoreHelper());
    }
}