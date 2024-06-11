<?php

declare(strict_types=1);

namespace N98\Magento\Command\Design;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle demo store notice command
 *
 * @package N98\Magento\Command\Design
 */
class DemoNoticeCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'design:demo-notice';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles demo store notice for a store view';

    /**
     * @var string
     */
    protected string $configPath = 'design/head/demonotice';

    /**
     * @var string
     */
    protected string $toggleComment = 'Demo Notice';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
