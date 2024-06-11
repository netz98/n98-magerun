<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle admin notifications command
 *
 * @package N98\Magento\Command\Admin
 */
class DisableNotificationsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:notifications';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles admin notifications.';

    /**
     * @var string
     */
    protected string $configPath = 'advanced/modules_disable_output/Mage_AdminNotification';

    /**
     * @var string
     */
    protected string $toggleComment = 'Admin Notifications';

    /**
     * @var string
     */
    protected string $trueName = 'hidden';

    /**
     * @var string
     */
    protected string $falseName = 'visible';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_GLOBAL;
}
