<?php

namespace N98\Magento\Command\Config;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:search')
            ->setDescription('Search system configuration descriptions.')
            ->setHelp(
                <<<EOT
                Searches the merged system.xml configuration tree <labels/> and <comments/> for the indicated text.
EOT
            )
            ->addArgument('text', InputArgument::REQUIRED, 'The text to search for');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $this->writeSection($output, 'Config Search');

        $searchString = $input->getArgument('text');
        $system = \Mage::getConfig()->loadModulesConfiguration('system.xml');
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
    }

    /**
     * @param string $searchString
     * @param string $system
     *
     * @return array
     */
    protected function _searchConfiguration($searchString, $system)
    {
        $xpathSections = array(
            'sections/*',
            'sections/*/groups/*',
            'sections/*/groups/*/fields/*',
        );

        $matches = array();
        foreach ($xpathSections as $xpath) {
            $tmp = $this->_searchConfigurationNodes(
                $searchString,
                $system->getNode()->xpath($xpath)
            );
            $matches = array_merge($matches, $tmp);
        }

        return $matches;
    }

    /**
     * @param string $searchString
     * @param array  $nodes
     *
     * @return array
     */
    protected function _searchConfigurationNodes($searchString, $nodes)
    {
        $matches = array();
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
     * @param object $node
     *
     * @return bool|\stdClass
     */
    protected function _searchNode($searchString, $node)
    {
        $match = new \stdClass;
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

    /**
     * @param object $node
     *
     * @return string
     */
    protected function _getNodeType($node)
    {
        $parent = current($node->xpath('parent::*'));
        $grandParent = current($parent->xpath('parent::*'));
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
     * @param object $match
     *
     * @return string
     * @throws RuntimeException
     */
    protected function _getPhpMageStoreConfigPathFromMatch($match)
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
     * @param object $match
     *
     * @return string
     * @throws RuntimeException
     */
    protected function _getPathFromMatch($match)
    {
        switch ($match->type) {
            case 'section':
                return (string) $match->node->label . ' -> ... -> ...';

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
