<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:search')
            ->setDescription('Search system configuration descriptions.')
            ->setHelp(<<<EOT
Searches the merged system.xml configuration tree <labels/> and <comments/> for the indicated text.
EOT
                )
            ->addArgument('text', InputArgument::REQUIRED, 'The text to search for')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);    
        
        $search_string = $input->getArgument('text');
        
        $this->detectMagento($output, true);
        $output->writeln("Initializing Magento");
        if (!$this->initMagento()) {
            $output->writeln("Could not init Magento");
        }
        
        $output->writeln("Loading system configuration");
        $system = \Mage::getConfig()->loadModulesConfiguration('system.xml');
        
        $matches = $this->_searchConfiguration($search_string,$system);
        
        //main output
        $output->writeln('');
        foreach($matches as $match)
        {            
            $output->writeln('Found a <info>' . $match->type . '</info> with a match');            
            $output->writeln('  ' . $this->_getPhpMageStoreConfigPathFromMatch($match));
            $output->writeln('  ' . $this->_getPathFromMatch($match));
            
            if($match->match_type == 'comment')
            {
                $output->writeln('  ' .
                    str_ireplace(
                        $search_string,
                        '<info>'.$search_string.'</info>',
                        (string)$match->node->comment
                    )
                );
            }
            $output->writeln('');
        }
    }
    
    protected function _getPhpMageStoreConfigPathFromMatch($match)
    {
        $path = '';
        switch($match->type)
        {
            case 'section':                
                $path = $match->node->getName();
                break;
            case 'field':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));            
                
                $grand  = current($parent->xpath('parent::*'));
                $grand  = current($grand->xpath('parent::*'));
                
                $path = $grand->getName() . '/' . $parent->getName() . '/' . $match->node->getName();
                break;
            case 'group':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));
                $path = $parent->getName() . '/' . $match->node->getName();
                break;
            default:
                exit(__METHOD__);
        }    
        return "Mage::getStoreConfig('".$path."')";
    }
    
    protected function _getPathFromMatch($match)
    {
        switch($match->type)
        {
            case 'section':
                return (string) $match->node->label . ' -> ... -> ...';
            case 'field':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));            
                
                $grand  = current($parent->xpath('parent::*'));
                $grand  = current($grand->xpath('parent::*'));
                
                return $grand->label . ' -> '.$parent->label.' -> <info>'.$match->node->label.'</info>';           
            case 'group':
                $parent = current($match->node->xpath('parent::*'));
                $parent = current($parent->xpath('parent::*'));
                return $parent->label . ' -> <info>'.$match->node->label.'</info> -> ...';
            default:
                exit(__METHOD__);
        }
        
    }
    
    protected function _searchConfiguration($search_string,$system)
    {
        $xpath_sections = array(
            'sections/*',
            'sections/*/groups/*',
            'sections/*/groups/*/fields/*'
        );
        
        $matches = array();
        foreach($xpath_sections as $xpath)
        {
            $tmp = $this->_searchConfigurationNodes(
                $search_string,
                $system->getNode()->xpath($xpath)
                //$system->getNode($section)
            );
            $matches = array_merge($matches, $tmp);
        }
        return $matches;
    }
    
    protected function _searchConfigurationNodes($search_string,$nodes)
    {
        $matches = array();
        foreach($nodes as $node)
        {        
            $match = $this->_searchNode($search_string, $node);
            if($match)
            {
                $matches[] = $match;
            }         
        }  
        return $matches;
    }
    
    protected function _getNodeType($node)
    {
        $parent         = current($node->xpath('parent::*'));
        $grand_parent   = current($parent->xpath('parent::*'));
        switch($grand_parent->getName())
        {
            case 'config':
                return 'section';
        }
        
        switch($parent->getName())
        {
            case 'groups':
                return 'group';
            case 'fields':
                return 'field';
            default:
                return 'unknown';
        }
        
    }
    
    protected function _searchNode($search_string, $node)
    {
        $match = new \stdClass;
        $match->type            = $this->_getNodeType($node);
        if(stristr((string)$node->label, $search_string))  
        {
            
            $match->match_type  = 'label';
            $match->node        = $node;
            return $match;
        }
        
        if(stristr((string)$node->comment, $search_string))  
        {
            $match->match_type  = 'comment';
            $match->node        = $node;            
            return $match;
        }       
        
        return false;
    }
    
}