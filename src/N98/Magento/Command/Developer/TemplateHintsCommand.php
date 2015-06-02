<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class TemplateHintsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:template-hints';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles template hints';

    /**
     * @var string
     */
    protected $toggleComment = 'Template Hints';

    /**
     * @var string
     */
    protected $configPath = 'dev/debug/template_hints';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW;
}
