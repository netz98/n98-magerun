<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Settings;

use Mage_Core_Model_Store;
use N98\Magento\Command\System\Check\Result;

/**
 * Class BaseUrlCheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
abstract class BaseUrlCheckAbstract extends CheckAbstract
{
    protected string $class = 'abstract';

    protected function initConfigPaths(): void
    {
        $this->registerStoreConfigPath('baseUrl', 'web/' . $this->class . '/base_url');
    }

    protected function checkSettings(Result $result, ?Mage_Core_Model_Store $mageCoreModelStore, string $baseUrl): void
    {
        $errorMessage = 'Wrong hostname configured. <info>Hostname must contain a dot</info>';

        /** @var string $host */
        $host    = parse_url($baseUrl, PHP_URL_HOST);
        $isValid = (bool) strstr($host, '.');

        $storeCode = $mageCoreModelStore instanceof Mage_Core_Model_Store ? $mageCoreModelStore->getCode() : 'n/a';

        $result->setStatus($isValid);
        if ($isValid) {
            $result->setMessage(
                '<info>' . ucfirst($this->class) . ' BaseURL: <comment>' . $baseUrl . '</comment> of Store: <comment>' .
                $storeCode . '</comment> - OK',
            );
        } else {
            $result->setMessage(
                '<error>Invalid ' . ucfirst($this->class) . ' BaseURL: <comment>' . $baseUrl .
                '</comment> of Store: <comment>' . $storeCode . '</comment> ' . $errorMessage . '</error>',
            );
        }
    }
}
