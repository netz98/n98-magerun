<?php

namespace N98\Magento\Command\Developer\Translate;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class InlineAdminCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $configPath = 'dev/translate_inline/active_admin';

    /**
     * @var string
     */
    protected $toggleComment = 'Inline Translation (Admin)';

    /**
     * @var string
     */
    protected $commandName = 'dev:translate:admin';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggle inline translation tool for admin';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_GLOBAL;
}