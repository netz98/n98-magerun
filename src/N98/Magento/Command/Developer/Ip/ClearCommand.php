<?php

namespace N98\Magento\Command\Developer\Ip;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class ClearCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:ip:clear';

    /**
     * @var string
     */

    /**
     * @var string
     */
    protected $commandDescription = 'Clear list of ip addresses for developers';

    /**
     * @var string
     */
    protected $toggleComment = 'Template Hints';


    /**
     * Configuration key in Magento
     * @var string
     */
    protected $configPath = 'dev/restrict/allow_ips';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;

    /**
     * @var boolean
     */
    protected $deleteWithClear = true;
}
