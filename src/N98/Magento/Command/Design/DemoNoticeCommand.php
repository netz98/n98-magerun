<?php

declare(strict_types=1);

namespace N98\Magento\Command\Design;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle demo notice command
 *
 * @package N98\Magento\Command\Design
 */
class DemoNoticeCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'design:demo-notice';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles demo store notice for a store view';

    protected string $configPath = 'design/head/demonotice';

    protected string $toggleComment = 'Demo Notice';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
