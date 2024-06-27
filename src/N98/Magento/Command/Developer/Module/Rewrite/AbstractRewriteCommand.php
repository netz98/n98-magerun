<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use DOMElement;
use Mage;
use Mage_Core_Model_Config_Element;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Finder\Finder;
use function array_combine;
use function array_fill;
use function array_merge;
use function count;
use function dom_import_simplexml;
use function is_countable;
use function is_dir;
use function simplexml_load_file;
use function str_replace;
use function substr;
use function trim;

/**
 * Class AbstractRewriteCommand
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
abstract class AbstractRewriteCommand extends AbstractCommand
{
    /**
        * @var string[]
     */
    protected array $_rewriteTypes = ['blocks', 'helpers', 'models'];

    /**
     * Return all rewrites
     *
     * @return array<string, array<string, array<int, string>>>
     */
    protected function loadRewrites(): array
    {
        $prototype = $this->_rewriteTypes;
        /** @var array<string, array<string, array<int, string>>> $return */
        $return =  array_combine(
            $prototype,
            array_fill(0, count($prototype), [])
        );

        // Load config of each module because modules can overwrite config each other. Global config is already merged
        $modules = $this->_getMageConfigNode('modules')->children();
        /**
         * @var string $moduleName
         * @var Mage_Core_Model_Config_Element $moduleData
         */
        foreach ($modules as $moduleName => $moduleData) {
            // Check only active modules
            if (!$moduleData->is('active')) {
                continue;
            }

            // Load config of module
            $configXmlFile = $this->_getMageConfig()->getModuleDir('etc', $moduleName) . DIRECTORY_SEPARATOR . 'config.xml';
            if (!\is_readable($configXmlFile)) {
                continue;
            }

            $xml = simplexml_load_file($configXmlFile);
            if (!$xml) {
                continue;
            }

            $rewriteElements = $xml->xpath('//*/*/rewrite');
            foreach ($rewriteElements as $element) {
                /** @var DOMElement $domNode */
                $domNode = dom_import_simplexml($element);
                /** @var DOMElement $parent */
                $parent = $domNode->parentNode;
                /** @var DOMElement $parent */
                $parent = $parent->parentNode;
                /** @var string $type */
                $type = $parent->nodeName;

                if (!isset($return[$type])) {
                    continue;
                }

                foreach ($element->children() as $child) {
                    /** @var DOMElement $domNode */
                    $domNode = dom_import_simplexml($element);
                    /** @var DOMElement $parent */
                    $parent = $domNode->parentNode;
                    /** @var string $groupClassName */
                    $groupClassName = $parent->nodeName;

                    $modelName = $child->getName();
                    $return[$type][$groupClassName . '/' . $modelName][] = (string) $child;
                }
            }
        }

        return $return;
    }

    /**
     * Check codepools for core overwrites.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    protected function loadAutoloaderRewrites(): array
    {
        $return = $this->loadAutoloaderRewritesByCodepool('community');
        return array_merge($return, $this->loadAutoloaderRewritesByCodepool('local'));
    }

    /**
     * Searches for all rewrites over autoloader in "app/code/<codepool>" of
     * Mage, Zend, Varien namespaces.
     *
     * @param string $codePool
     * @return array<string, array<string, array<int, string>>>
     */
    protected function loadAutoloaderRewritesByCodepool(string $codePool): array
    {
        $return = [];
        $localCodeFolder = Mage::getBaseDir('code') . '/' . $codePool;

        $folders = [
            'Mage'       => $localCodeFolder . '/Mage',
            'Varien'     => $localCodeFolder . '/Varien',
            'Zend'       => $localCodeFolder . '/Zend'
        ];

        foreach ($folders as $vendorPrefix => $folder) {
            if (is_dir($folder)) {
                $finder = new Finder();
                $finder
                    ->files()
                    ->ignoreUnreadableDirs()
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
