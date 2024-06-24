<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use Mage_Core_Model_Config_Element;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Simplexml_Config;
use Varien_Simplexml_Element;

/**
 * Config search command
 *
 * @package N98\Magento\Command\Config
 */
class SearchCommand extends AbstractConfigCommand
{
    protected const XPATH_EXPR_PARENT = 'parent::*';

    public const COMMAND_ARGUMENT_TEXT = 'text';

    /**
     * @var string
     */
    protected static $defaultName = 'config:search';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Search system configuration descriptions.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_TEXT,
                InputArgument::REQUIRED,
                'The text to search for'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<EOT
                Searches the merged system.xml configuration tree <labels/> and <comments/> for the indicated text.
EOT;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeSection($output, 'Config Search');

        /** @var string $searchString */
        $searchString = $input->getArgument(self::COMMAND_ARGUMENT_TEXT);
        $system = $this->_getMageConfig()->loadModulesConfiguration('system.xml');
        $matches = $this->_searchConfiguration($searchString, $system);

        if (count($matches) > 0) {
            foreach ($matches as $match) {
                $output->writeln('Found a <comment>' . $match->type . '</comment> with a match');
                $output->writeln('  ' . $this->_getPhpMageStoreConfigPathFromMatch($match));
                $output->writeln('  ' . $this->_getPathFromMatch($match));

                if ($match->match_type == 'comment') {
                    $output->writeln(
                        '  ' .
                        str_ireplace(
                            $searchString,
                            '<info>' . $searchString . '</info>',
                            (string) $match->node->comment
                        )
                    );
                }
                $output->writeln('');
            }
        } else {
            $output->writeln('<info>No matches for <comment>' . $searchString . '</comment></info>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $searchString
     * @param Varien_Simplexml_Config $system
     * @return array<object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string, match_type: string}>
     */
    protected function _searchConfiguration(string $searchString, Varien_Simplexml_Config $system): array
    {
        $xpathSections = ['sections/*', 'sections/*/groups/*', 'sections/*/groups/*/fields/*'];

        $matches = [];
        foreach ($xpathSections as $xpath) {
            /** @var Varien_Simplexml_Element $node */
            $node = $system->getNode();
            /** @var array<object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string, match_type: string}> $elem */
            $elem = $node->xpath($xpath);
            $tmp = $this->_searchConfigurationNodes(
                $searchString,
                $elem
            );

            /** @var array<object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string, match_type: string}> $matches */
            $matches = array_merge($matches, $tmp);
        }

        return $matches;
    }

    /**
     * @param string $searchString
     * @param object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string, match_type: string}[] $nodes
     * @return object[]
     */
    protected function _searchConfigurationNodes(string $searchString, array $nodes): array
    {
        $matches = [];
        foreach ($nodes as $node) {
            $match = $this->_searchNode($searchString, $node);
            if ($match) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * @param string $searchString
     * @param object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string} $node
     * @return null|object
     */
    protected function _searchNode(string $searchString, object $node): ?object
    {
        $match = new stdClass();
        $match->type = $this->_getNodeType($node);
        if (stristr((string) $node->label, $searchString)) {
            $match->match_type = 'label';
            $match->node = $node;

            return $match;
        }

        if (stristr((string) $node->comment, $searchString)) {
            $match->match_type = 'comment';
            $match->node = $node;

            return $match;
        }

        return null;
    }

    /**
     * @param object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string} $node
     * @return string
     */
    protected function _getNodeType(object $node)
    {
        /** @var Mage_Core_Model_Config_Element $node */
        $parent = current($node->xpath(self::XPATH_EXPR_PARENT));
        /** @var Mage_Core_Model_Config_Element $parent */
        $grandParent = current($parent->xpath(self::XPATH_EXPR_PARENT));
        /** @var Mage_Core_Model_Config_Element $grandParent */
        if ($grandParent->getName() == 'config') {
            return 'section';
        }

        switch ($parent->getName()) {
            case 'groups':
                return 'group';

            case 'fields':
                return 'field';

            default:
                return 'unknown';
        }
    }

    /**
     * @param object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string} $match
     * @return string
     * @throws RuntimeException
     */
    protected function _getPhpMageStoreConfigPathFromMatch(object $match): string
    {
        switch ($match->type) {
            case 'section':
                $path = $match->node->getName();
                break;

            case 'field':
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($match->node->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($parent->xpath(self::XPATH_EXPR_PARENT));

                /** @var Mage_Core_Model_Config_Element $grand */
                $grand = current($parent->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $grand */
                $grand = current($grand->xpath(self::XPATH_EXPR_PARENT));

                $path = $grand->getName() . '/' . $parent->getName() . '/' . $match->node->getName();
                break;

            case 'group':
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($match->node->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($parent->xpath(self::XPATH_EXPR_PARENT));
                $path = $parent->getName() . '/' . $match->node->getName();
                break;

            default:
                // @TODO Why?
                throw new RuntimeException(__METHOD__);
        }

        return "Mage::getStoreConfig('" . $path . "')";
    }

    /**
     * @param object{type: string, node: Mage_Core_Model_Config_Element, label: string, comment: string} $match
     * @return string
     * @throws RuntimeException
     */
    protected function _getPathFromMatch(object $match): string
    {
        switch ($match->type) {
            case 'section':
                return (string) $match->node->label . ' -> ... -> ...';

            case 'field':
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($match->node->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($parent->xpath(self::XPATH_EXPR_PARENT));

                /** @var Mage_Core_Model_Config_Element $grand */
                $grand = current($parent->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $grand */
                $grand = current($grand->xpath(self::XPATH_EXPR_PARENT));

                return $grand->label . ' -> ' . $parent->label . ' -> <info>' . $match->node->label . '</info>';

            case 'group':
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($match->node->xpath(self::XPATH_EXPR_PARENT));
                /** @var Mage_Core_Model_Config_Element $parent */
                $parent = current($parent->xpath(self::XPATH_EXPR_PARENT));
                return $parent->label . ' -> <info>' . $match->node->label . '</info> -> ...';

            default:
                // @TODO Why?
                throw new RuntimeException(__METHOD__);
        }
    }
}
