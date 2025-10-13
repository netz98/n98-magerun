<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use Mage;
use RuntimeException;
use SimpleXMLElement;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Simplexml_Config;

/**
 * Search config command
 *
 * @package N98\Magento\Command\Config
 */
class SearchCommand extends AbstractConfigCommand
{
    protected function configure(): void
    {
        $this
            ->setName('config:search')
            ->setDescription('Search system configuration descriptions.')
            ->addArgument('text', InputArgument::REQUIRED, 'The text to search for');
    }

    public function getHelp(): string
    {
        return <<<HELP
Searches the merged system.xml configuration tree <labels/> and <comments/> for the indicated text.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $this->writeSection($output, 'Config Search');

        $searchString = $input->getArgument('text');
        $system = Mage::getConfig()->loadModulesConfiguration('system.xml');
        $matches = $this->_searchConfiguration($searchString, $system);

        if ($matches !== []) {
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
                            (string) $match->node->comment,
                        ),
                    );
                }

                $output->writeln('');
            }
        } else {
            $output->writeln('<info>No matches for <comment>' . $searchString . '</comment></info>');
        }

        return Command::SUCCESS;
    }

    protected function _searchConfiguration(string $searchString, Varien_Simplexml_Config $varienSimplexmlConfig): array
    {
        $xpathSections = ['sections/*', 'sections/*/groups/*', 'sections/*/groups/*/fields/*'];

        $matches = [];
        foreach ($xpathSections as $xpathSection) {
            $systemNode = $varienSimplexmlConfig->getNode();
            if ($systemNode) {
                $tmp = $this->_searchConfigurationNodes(
                    $searchString,
                    $systemNode->xpath($xpathSection),
                );
                $matches = array_merge($matches, $tmp);
            }
        }

        return $matches;
    }

    /**
     * @return \stdClass[]
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
     * @return false|stdClass
     */
    protected function _searchNode(string $searchString, SimpleXMLElement $node)
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

        return false;
    }

    protected function _getNodeType(SimpleXMLElement $node): string
    {
        /** @var SimpleXMLElement $parent */
        $parent         = current($node->xpath('parent::*'));
        /** @var SimpleXMLElement $grandParent */
        $grandParent    = current($parent->xpath('parent::*'));
        if ($grandParent->getName() === 'config') {
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
     * @throws RuntimeException
     */
    protected function _getPhpMageStoreConfigPathFromMatch(object $match): string
    {
        switch ($match->type) {
            case 'section':
                $path = $match->node->getName();
                break;

            case 'field':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));

                $grand = current($parent->xpath('parent::*'));
                $grand = current($grand->xpath('parent::*'));

                $path = $grand->getName() . '/' . $parent->getName() . '/' . $match->node->getName();
                break;

            case 'group':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));
                $path = $parent->getName() . '/' . $match->node->getName();
                break;

            default:
                // @TODO Why?
                throw new RuntimeException(__METHOD__);
        }

        return "Mage::getStoreConfig('" . $path . "')";
    }

    /**
     * @throws RuntimeException
     */
    protected function _getPathFromMatch(object $match): string
    {
        switch ($match->type) {
            case 'section':
                return $match->node->label . ' -> ... -> ...';

            case 'field':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));

                $grand = current($parent->xpath('parent::*'));
                $grand = current($grand->xpath('parent::*'));

                return $grand->label . ' -> ' . $parent->label . ' -> <info>' . $match->node->label . '</info>';

            case 'group':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));
                return $parent->label . ' -> <info>' . $match->node->label . '</info> -> ...';

            default:
                // @TODO Why?
                throw new RuntimeException(__METHOD__);
        }
    }
}
