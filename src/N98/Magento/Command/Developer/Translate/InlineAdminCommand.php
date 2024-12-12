<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle admin inline translation command
 *
 * @package N98\Magento\Command\Developer\Translate
 */
class InlineAdminCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:translate:admin';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggle inline translation tool for admin';

    protected string $configPath = 'dev/translate_inline/active_admin';

    protected string $toggleComment = 'Inline Translation (Admin)';

    protected string $scope = self::SCOPE_GLOBAL;

    /**
     * If required, handle the output and possible change of the developer IP restrictions
     */
    protected function _afterSave(Mage_Core_Model_Store $mageCoreModelStore, bool $disabled): void
    {
        $this->detectAskAndSetDeveloperIp($mageCoreModelStore, $disabled);
    }
}
