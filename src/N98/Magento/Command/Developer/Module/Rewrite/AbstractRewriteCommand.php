<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\AbstractMagentoCommand;

abstract class AbstractRewriteCommand extends AbstractMagentoCommand
{
    /**
     * Return all rewrites
     *
     * @return array
     */
    protected function loadRewrites()
    {
        $return = array(
            'blocks',
            'models',
            'helpers',
        );

        // Load config of each module because modules can overwrite config each other. Globl config is already merged
        $modules = \Mage::getConfig()->getNode('modules')->children();
        foreach ($modules as $moduleName => $moduleData) {
            // Check only active modules
            if (!$moduleData->is('active')) {
                continue;
            }

            // Load config of module
            $xml = \simplexml_load_file(\Mage::getConfig()->getModuleDir('etc', $moduleName) . DIRECTORY_SEPARATOR . 'config.xml');
            if ($xml) {
                $rewriteElements = $xml->xpath('//rewrite');
                foreach ($rewriteElements as $element) {
                    foreach ($element->children() as $child) {
                        $type = \simplexml_import_dom(dom_import_simplexml($element)->parentNode->parentNode)->getName();
                        $groupClassName = \simplexml_import_dom(dom_import_simplexml($element)->parentNode)->getName();
                        if (!isset($return[$type][$groupClassName . '/' . $child->getName()])) {
                            $return[$type][$groupClassName . '/' . $child->getName()] = array();
                        }
                        $return[$type][$groupClassName . '/' . $child->getName()][] = (string) $child;
                    }
                }
            }
        }

        return $return;
    }
}