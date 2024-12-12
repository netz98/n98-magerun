<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle template hints command
 *
 * @package N98\Magento\Command\Developer
 */
class TemplateHintsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:template-hints';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles template hints';

    protected string $toggleComment = 'Template Hints';

    protected string $configPath = 'dev/debug/template_hints';

    protected string $scope = self::SCOPE_STORE_VIEW;

    protected bool $withAdminStore = true;

    /**
     * If required, handle the output and possible change of the developer IP restrictions
     */
    protected function _afterSave(Mage_Core_Model_Store $mageCoreModelStore, bool $disabled): void
    {
        $this->detectAskAndSetDeveloperIp($mageCoreModelStore, $disabled);
    }
}
