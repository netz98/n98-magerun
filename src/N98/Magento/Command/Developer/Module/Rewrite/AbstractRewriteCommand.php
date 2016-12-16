<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Finder\Finder;

abstract class AbstractRewriteCommand extends AbstractMagentoCommand
{
    protected $_rewriteTypes = array(
        'blocks',
        'helpers',
        'models',
    );

    /**
     * Return all rewrites
     *
     * @return array
     */
    protected function loadRewrites()
    {
        $prototype = $this->_rewriteTypes;
        $return = array_combine($prototype, array_fill(0, count($prototype), array()));

        // Load config of each module because modules can overwrite config each other. Global config is already merged
        $modules = \Mage::getConfig()->getNode('modules')->children();
        foreach ($modules as $moduleName => $moduleData) {
            // Check only active modules
            if (!$moduleData->is('active')) {
                continue;
            }

            // Load config of module
            $configXmlFile = \Mage::getConfig()->getModuleDir('etc', $moduleName) . DIRECTORY_SEPARATOR . 'config.xml';
            if (!is_readable($configXmlFile)) {
                continue;
            }

            $xml = \simplexml_load_file($configXmlFile);
            if (!$xml) {
                continue;
            }

            $rewriteElements = $xml->xpath('//*/*/rewrite');
            foreach ($rewriteElements as $element) {
                $type = dom_import_simplexml($element)->parentNode->parentNode->nodeName;
                if (!isset($return[$type])) {
                    continue;
                }

                foreach ($element->children() as $child) {
                    $groupClassName = dom_import_simplexml($element)->parentNode->nodeName;
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
     * @return array
     */
    protected function loadAutoloaderRewrites()
    {
        $return = $this->loadAutoloaderRewritesByCodepool('community');
        $return = array_merge($return, $this->loadAutoloaderRewritesByCodepool('local'));

        return $return;
    }

    /**
     * Searches for all rewrites over autoloader in "app/code/<codepool>" of
     * Mage, Enterprise Zend, Varien namespaces.
     *
     * @param string $codePool
     * @return array
     */
    protected function loadAutoloaderRewritesByCodepool($codePool)
    {
        $return = array();
        $localCodeFolder = \Mage::getBaseDir('code') . '/' . $codePool;

        $folders = array(
            'Mage'       => $localCodeFolder . '/Mage',
            'Enterprise' => $localCodeFolder . '/Enterprise',
            'Varien'     => $localCodeFolder . '/Varien',
            'Zend'       => $localCodeFolder . '/Zend',
        );

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
