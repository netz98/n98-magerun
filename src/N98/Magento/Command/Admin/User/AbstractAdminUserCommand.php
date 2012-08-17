<?php

namespace N98\Magento\Command\Admin\User;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractAdminUserCommand extends AbstractMagentoCommand
{
    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getUserModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Admin_Model_User');
        } else {
            return \Mage::getModel('admin/user');
        }
    }
}
