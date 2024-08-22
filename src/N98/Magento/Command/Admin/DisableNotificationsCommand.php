<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle admin notification command
 *
 * @package N98\Magento\Command\Admin
 */
class DisableNotificationsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'admin:notifications';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles admin notifications';

    /**
     * @var string
     */
    protected $configPath = 'advanced/modules_disable_output/Mage_AdminNotification';

    /**
     * @var string
     */
    protected $toggleComment = 'Admin Notifications';

    /**
     * @var string
     */
    protected $trueName = 'hidden';

    /**
     * @var string
     */
    protected $falseName = 'visible';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_GLOBAL;
}
