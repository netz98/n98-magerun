<?php

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use N98\Util\Console\Helper\TableHelper;
use Parameter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InfoCommand
 * @package N98\Magento\Command\Developer\Theme
 */
class InfoCommand extends AbstractMagentoCommand
{
    public const THEMES_EXCEPTION = '_ua_regexp';

    /**
     * @var array
     */
    protected $_configNodes = ['Theme translations' => 'design/theme/locale'];

    /**
     * @var array
     */
    protected $_configNodesWithExceptions = ['Design Package Name' => 'design/package/name', 'Theme template'      => 'design/theme/template', 'Theme skin'          => 'design/theme/skin', 'Theme layout'        => 'design/theme/layout', 'Theme default'       => 'design/theme/default'];

    protected function configure()
    {
        $this
            ->setName('dev:theme:info')
            ->setDescription('Displays settings of current design on particular store view');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        foreach (Mage::app()->getWebsites() as $website) {
            /* @var \Mage_Core_Model_Website $website */
            foreach ($website->getStores() as $store) {
                /* @var \Mage_Core_Model_Store $store */
                $this->_displayTable($output, $store);
            }
        }
        return 0;
    }

    protected function _displayTable(OutputInterface $output, Mage_Core_Model_Store $store)
    {
        $this->writeSection(
            $output,
            'Current design setting on store: ' . $store->getWebsite()->getCode() . '/' . $store->getCode()
        );
        $storeInfoLines = $this->_parse($this->_configNodesWithExceptions, $store, true);
        $storeInfoLines = array_merge($storeInfoLines, $this->_parse($this->_configNodes, $store));

        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders([Parameter::class, 'Value'])
            ->renderByFormat($output, $storeInfoLines);

        return $this;
    }

    /**
     * @return array
     */
    protected function _parse(array $nodes, Mage_Core_Model_Store $store, $withExceptions = false)
    {
        $result = [];

        foreach ($nodes as $nodeLabel => $node) {
            $result[] = [$nodeLabel, (string) Mage::getConfig()->getNode(
                $node,
                AbstractMagentoStoreConfigCommand::SCOPE_STORE_VIEW,
                $store->getCode()
            )];
            if ($withExceptions) {
                $result[] = [$nodeLabel . ' exceptions', $this->_parseException($node, $store)];
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function _parseException($node, Mage_Core_Model_Store $store)
    {
        $exception = (string) Mage::getConfig()->getNode(
            $node . self::THEMES_EXCEPTION,
            AbstractMagentoStoreConfigCommand::SCOPE_STORE_VIEW,
            $store->getCode()
        );

        if (empty($exception)) {
            return '';
        }

        $exceptions = unserialize($exception);
        $result = [];
        foreach ($exceptions as $expression) {
            $result[] = 'Matched Expression: ' . $expression['regexp'];
            $result[] = 'Value: ' . $expression['value'];
        }

        return implode("\n", $result);
    }
}
