<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Theme info command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class InfoCommand extends AbstractMagentoCommand
{
    public const THEMES_EXCEPTION = '_ua_regexp';

    /**
     * @var array<string, string>
     */
    protected array $_configNodes = [
        'Theme translations' => 'design/theme/locale',
    ];

    /**
     * @var array<string, string>
     */
    protected array $_configNodesWithExceptions = [
        'Design Package Name' => 'design/package/name',
        'Theme template'      => 'design/theme/template',
        'Theme skin'          => 'design/theme/skin',
        'Theme layout'        => 'design/theme/layout',
        'Theme default'       => 'design/theme/default',
    ];

    protected function configure(): void
    {
        $this
            ->setName('dev:theme:info')
            ->setDescription('Displays settings of current design on particular store view');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getStores() as $store) {
                $this->_displayTable($output, $store);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return $this
     */
    protected function _displayTable(OutputInterface $output, Mage_Core_Model_Store $mageCoreModelStore)
    {
        $website        = $mageCoreModelStore->getWebsite();
        $websiteCode    = $website ? $website->getCode() . '/' : '';

        $this->writeSection(
            $output,
            'Current design setting on store: ' . $websiteCode . $mageCoreModelStore->getCode(),
        );
        $storeInfoLines = $this->_parse($this->_configNodesWithExceptions, $mageCoreModelStore, true);
        $storeInfoLines = array_merge($storeInfoLines, $this->_parse($this->_configNodes, $mageCoreModelStore));

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Parameter', 'Value'])
            ->renderByFormat($output, $storeInfoLines);

        return $this;
    }

    /**
     * @param array<string, string> $nodes
     */
    protected function _parse(array $nodes, Mage_Core_Model_Store $mageCoreModelStore, bool $withExceptions = false): array
    {
        $result = [];

        foreach ($nodes as $nodeLabel => $node) {
            $result[] = [$nodeLabel, (string) Mage::getConfig()->getNode(
                $node,
                AbstractMagentoStoreConfigCommand::SCOPE_STORE_VIEW,
                $mageCoreModelStore->getCode(),
            )];
            if ($withExceptions) {
                $result[] = [$nodeLabel . ' exceptions', $this->_parseException($node, $mageCoreModelStore)];
            }
        }

        return $result;
    }

    protected function _parseException(string $node, Mage_Core_Model_Store $mageCoreModelStore): string
    {
        $exception = (string) Mage::getConfig()->getNode(
            $node . self::THEMES_EXCEPTION,
            AbstractMagentoStoreConfigCommand::SCOPE_STORE_VIEW,
            $mageCoreModelStore->getCode(),
        );

        if ($exception === '' || $exception === '0') {
            return '';
        }

        $exceptions = unserialize($exception);
        $result = [];
        foreach ($exceptions as $exception) {
            $result[] = 'Matched Expression: ' . $exception['regexp'];
            $result[] = 'Value: ' . $exception['value'];
        }

        return implode("\n", $result);
    }
}
