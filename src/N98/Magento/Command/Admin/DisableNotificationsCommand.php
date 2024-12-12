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

    protected string $configPath = 'advanced/modules_disable_output/Mage_AdminNotification';

    protected string $toggleComment = 'Admin Notifications';

    protected string $trueName = 'hidden';

    protected string $falseName = 'visible';

    protected string $scope = self::SCOPE_GLOBAL;
}
