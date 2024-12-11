<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use Mage;
use Mage_Core_Model_Config_Element;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Finder\Finder;

use function simplexml_load_file;

/**
 * Class AbstractRewriteCommand
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
abstract class AbstractRewriteCommand extends AbstractMagentoCommand
{
    protected array $_rewriteTypes = ['blocks', 'helpers', 'models'];

    /**
     * Return all rewrites
     */
    protected function loadRewrites(): array
    {
        $prototype      = $this->_rewriteTypes;
        /** @var array $return */
        $return         = array_combine($prototype, array_fill(0, count($prototype), []));

        // Load config of each module because modules can overwrite config each other. Global config is already merged
        /** @var Mage_Core_Model_Config_Element $modulesNode */
        $modulesNode    = Mage::getConfig()->getNode('modules');
        $modules        = $modulesNode->children();

        /**
         * @var  string $moduleName
         * @var  Mage_Core_Model_Config_Element $moduleData
         */
        foreach ($modules as $moduleName => $moduleData) {
            // Check only active modules
            if (!$moduleData->is('active')) {
                continue;
            }

            // Load config of module
            $configXmlFile = Mage::getConfig()->getModuleDir('etc', $moduleName) . DIRECTORY_SEPARATOR . 'config.xml';
            if (!is_readable($configXmlFile)) {
                continue;
            }

            $xml = simplexml_load_file($configXmlFile);
            if (!$xml) {
                continue;
            }

            $rewriteElements = $xml->xpath('//*/*/rewrite');
            foreach ($rewriteElements as $rewriteElement) {
                $rewriteDomElement = dom_import_simplexml($rewriteElement);
                if (!$rewriteDomElement) {
                    continue;
                }

                $type = $rewriteDomElement->parentNode->parentNode->nodeName;
                if (!isset($return[$type])) {
                    continue;
                }

                foreach ($rewriteElement->children() as $child) {
                    $childDomElement    = dom_import_simplexml($rewriteElement);
                    if (!$childDomElement) {
                        continue;
                    }

                    $groupClassName     = $childDomElement->parentNode->nodeName;
                    $modelName          = $child->getName();
                    $return[$type][$groupClassName . '/' . $modelName][] = (string) $child;
                }
            }
        }

        return $return;
    }

    /**
     * Check code-pools for core overwrites.
     */
    protected function loadAutoloaderRewrites(): array
    {
        $return = $this->loadAutoloaderRewritesByCodepool('community');
        return array_merge($return, $this->loadAutoloaderRewritesByCodepool('local'));
    }

    /**
     * Searches for all rewrites over autoloader in "app/code/<codepool>" of
     * Mage, Enterprise Zend, Varien namespaces.
     */
    protected function loadAutoloaderRewritesByCodepool(string $codePool): array
    {
        $return = [];
        $localCodeFolder = Mage::getBaseDir('code') . '/' . $codePool;

        $folders = [
            'Mage'       => $localCodeFolder . '/Mage',
            'Enterprise' => $localCodeFolder . '/Enterprise',
            'Varien'     => $localCodeFolder . '/Varien',
            'Zend'       => $localCodeFolder . '/Zend',
        ];

        foreach ($folders as $vendorPrefix => $folder) {
            if (is_dir($folder)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->ignoreUnreadableDirs(true)
                    ->followLinks()
                    ->in($folder);
                foreach ($finder as $file) {
                    $classFile = trim(str_replace($folder, '', $file->getPathname()), '/');
                    $className = $vendorPrefix . '_' . str_replace(DIRECTORY_SEPARATOR, '_', $classFile);
                    $className = substr($className, 0, -4); // replace .php extension
                    $return['autoload: ' . $vendorPrefix][$className][] = $className;
                }
            }
        }

        return $return;
    }
}
