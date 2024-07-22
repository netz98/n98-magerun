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
    protected $scope = self::SCOPE_GLOBAL;

    /**
     * If required, handle the output and possible change of the developer IP restrictions
     *
     * @param Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _afterSave(Mage_Core_Model_Store $store, bool $disabled): void
    {
        $this->detectAskAndSetDeveloperIp($store, $disabled);
    }
}
