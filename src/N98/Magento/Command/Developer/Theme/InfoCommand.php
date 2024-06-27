<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage_Core_Model_Store;
use Mage_Core_Model_Website;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\AbstractStoreConfigCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Theme info command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class InfoCommand extends AbstractCommand
{
    public const THEMES_EXCEPTION = '_ua_regexp';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:theme:info';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Displays settings of current design on particular store view.';

    /**
     * @var array<string, string>
     */
    protected array $_configNodes = ['Theme translations' => 'design/theme/locale'];

    /**
     * @var array<string, string>
     */
    protected array $_configNodesWithExceptions = [
        'Design Package Name' => 'design/package/name',
        'Theme template'      => 'design/theme/template',
        'Theme skin'          => 'design/theme/skin',
        'Theme layout'        => 'design/theme/layout',
        'Theme default'       => 'design/theme/default'
    ];

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->_getMage()->getWebsites() as $website) {
            foreach ($website->getStores() as $store) {
                $this->_displayTable($output, $store);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    protected function _displayTable(OutputInterface $output, Mage_Core_Model_Store $store): InfoCommand
    {
        /** @var Mage_Core_Model_Website $website */
        $website = $store->getWebsite();

        $this->writeSection(
            $output,
            'Current design setting on store: ' . $website->getCode() . '/' . $store->getCode()
        );
        $storeInfoLines = $this->_parse($this->_configNodesWithExceptions, $store, true);
        $storeInfoLines = array_merge($storeInfoLines, $this->_parse($this->_configNodes, $store));

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Parameter', 'Value'])
            ->renderByFormat($output, $storeInfoLines);

        return $this;
    }

    /**
     * @param array<string, string> $nodes
     * @param Mage_Core_Model_Store $store
     * @param bool $withExceptions
     * @return array<int, array<int, string>>
     */
    protected function _parse(array $nodes, Mage_Core_Model_Store $store, bool $withExceptions = false): array
    {
        $result = [];

        foreach ($nodes as $nodeLabel => $node) {
            $result[] = [
                $nodeLabel,
                (string) $this->_getMageConfig()->getNode(
                    $node,
                    AbstractStoreConfigCommand::SCOPE_STORE_VIEW,
                    $store->getCode()
                )
            ];
            if ($withExceptions) {
                $result[] = [
                    $nodeLabel . ' exceptions',
                    $this->_parseException($node, $store)
                ];
            }
        }

        return $result;
    }

    /**
     * @param string $node
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    protected function _parseException(string $node, Mage_Core_Model_Store $store): string
    {
        $exception = (string) $this->_getMageConfig()->getNode(
            $node . self::THEMES_EXCEPTION,
            AbstractStoreConfigCommand::SCOPE_STORE_VIEW,
            $store->getCode()
        );

        if (empty($exception)) {
            return '';
        }

        /** @var array<int, array<string, string>> $exceptions */
        $exceptions = unserialize($exception);
        $result = [];
        foreach ($exceptions as $expression) {
            $result[] = 'Matched Expression: ' . $expression['regexp'];
            $result[] = 'Value: ' . $expression['value'];
        }

        return implode("\n", $result);
    }
}
