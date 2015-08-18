<?php

namespace N98\Magento\Command\Design;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class DemoNoticeCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $configPath = 'design/head/demonotice';

    /**
     * @var string
     */
    protected $toggleComment = 'Demo Notice';

    /**
     * @var string
     */
    protected $commandName = 'design:demo-notice';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles demo store notice for a store view';

    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
