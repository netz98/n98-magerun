<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Magento\Command\AbstractMagentoCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputOption;

class ValidateExtensionCommand extends AbstractMagentoCommand
{
    protected $_connectConfig=false;
    
    protected function configure()
    {
        $this
            ->setName('extension:validate')
            ->addArgument('package', InputArgument::OPTIONAL, 'Package_Module to check')
            ->addOption('skip-file',null, InputOption::VALUE_NONE, 'If set, command will skip reporting the existence of package files')
            ->addOption('skip-hash',null, InputOption::VALUE_NONE, 'If set, command will skip validating the package file hashes')
            ->addOption('full-report',null, InputOption::VALUE_NONE, 'If set, command will report on ALL package files')
            ->addOption('include-default',null, InputOption::VALUE_NONE, 'Include default packages that ship with Magento Connect')
            ->setDescription('Reads Magento Connect Config, and checks that installed package files are really there')
        ;

        $help = <<<HELP
Reads Magento Connect config, and checks that installed
package files are really there.

Magento Connect is Magento's built in package manager.  It's
notorious for failing to completly install extension files
if file permissions are setup incorrectly, while telling a
Connect user that everything is OK.

This command scans a the list of packages installed via
Magento Connect, and uses the package manifest to check

1. If the file actually exists

2. If the file hash matches the hash from the manifest

A missing file indicates a package wasn't installed
correctly.  A non-matching hash *might* mean the file's been
changed by another process, or *might* mean the file is from
a previous package version, or *might* mean the extension
packager failed to generate the hash correctly.

This is the madness of using software that lies.         

HELP;
        $this->setHelp($help);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {    
        $this->_init($output);

        $packages = array($input->getArgument('package'));        
        if($packages == array(NULL))
        {
            $packages = $this->_getInstalledPackages();    
        }
        $to_skip = array();
        if(!$input->getOption('include-default'))
        {
            $to_skip = $this->_getBasePackages();
        }
        foreach($packages as $package)
        {
            if(in_array($package, $to_skip)){ continue; }
            $output->writeln('');
            $output->writeln($package);
            $output->writeln('--------------------------------------------------');
            $output->writeln('');
            $this->_validateSpecificPackage($package, $output, $input);
        }
        $output->writeln('');
    }

    protected function _getSpecificPackageConfig($name)
    {
        $config = $this->_loadConfig();
        $packages = $config['channels_by_name']['community']['packages'];
        return $packages[$name];
    }

    protected function _getExtensionFileListFromSpecificConfig($config)
    {
        $xml    = simplexml_load_string($config['xml']);
        $contents = $xml->contents;
        $return = array();
        foreach($xml->contents->children() as $target)
        {
            $target_name = (string)$target['name'];
            $path_base = $this->_getBasePathFromTargetName($target_name);
            $files = $target->xpath('//file');
            $return = array();
            foreach($files as $file)        
            {
                $path = $this->_getPathOfFileNodeToTarget($file);
                $return[$path] = (string)$file['hash'];
            }
        }
        return $return;
    }
    
    protected function _getBasePathFromTargetName($target_name)
    {
        $paths = array(
            'mageetc'=>'app/etc',
            'magecommunity'=>'app/code/community',
            'magedesign'=>'app/design',
            'magelocale'=>'app/locale',
            "magelocal"=>'app/code/local',
            "magecore"=>'app/code/core',
            "magelib"=>'lib',
            "magemedia"=>'media',
            "mageskin"=>'skin',
            "mageweb"=>'.',
            "magetest"=>'tests',
            "mage"=>'.',
        );
        return $paths[$target_name];
    }

    protected function _getPathOfFileNodeToTarget($node, $path='')
    {
        if($node->getName() == 'target')
        {
            return $this->_getBasePathFromTargetName((string)$node['name']) .  $path;
        }
    
        $path = '/' . $node['name'] . $path;
        $parent = $this->_getParentNode($node);
        return $this->_getPathOfFileNodeToTarget($parent, $path);
    }

    protected function _getParentNode($node)
    {
        $parent = $node->xpath("..");
        return array_shift($parent);
    }

    protected function getPathOfFileNodeToTarget($node, $path='')
    {
        if($node->getName() == 'target')
        {
            return $this->_getBasePathFromTargetName((string)$node['name']) .  $path;
        }
    
        $path = '/' . $node['name'] . $path;
        $parent = $this->_getParentNode($node);
        return $this->_getPathOfFileNodeToTarget($parent, $path);
    }


    protected function _validateSpecificPackage($package, $output, $input)
    {        
        $config = $this->_getSpecificPackageConfig($package);
        $files = $this->_getExtensionFileListFromSpecificConfig($config);
        
        $path_base = \Mage::getBaseDir();
        foreach($files as $path=>$hash)
        {
            $path = $path_base . \DS . $path;
            $this->_optionOutput('Checking: ' . $path, 'full-report', $output, $input);
    
            if(file_exists($path))
            {
                $this->_optionOutput('    Path: OK', array('full-report','file'), $output, $input);
                
                if(md5(file_get_contents($path)) == $hash)
                {
                    $this->_optionOutput('    Hash: OK', array('full-report','hash'),$output, $input);
                }
                else
                {
                    $this->_optionOutput('Problem: ' . $path, 'hash', $output, $input);
                    $this->_optionOutput('    Hash: MISMATCH', 'hash', $output, $input);
                }
            }
            else
            {
                $this->_optionOutput('Problem: ' . $path, 'file', $output, $input);
                $this->_optionOutput('    Path: FILE NOT FOUND', 'file', $output, $input);                    
            }
        }
    }

    protected function _optionOutput($text, $type, $output, $input)
    {
        $type        = is_array($type) ? $type : array($type);
        
        $skip_hash   = $input->getOption('skip-hash');
        $skip_file   = $input->getOption('skip-file');
        $full_report = $input->getOption('full-report');
        
        if(in_array('full-report', $type) && !$full_report)
        {
            return;
        }

        if(in_array('hash', $type) && $skip_hash)
        {
            return;
        }

        if(in_array('file', $type) && $skip_file)
        {
            return;
        }
        
        $output->writeln($text);        
    }
    
    protected function _loadConfig()
    {
        if(!$this->_connectConfig)
        {
            $this->_connectConfig = file_get_contents(\Mage::getBaseDir() . '/downloader/cache.cfg');
            $this->_connectConfig = gzuncompress($this->_connectConfig);
            $this->_connectConfig = unserialize($this->_connectConfig);
        }    
        return $this->_connectConfig;
    }
    
    protected function _getInstalledPackages()
    {
        $config = $this->_loadConfig();
        $packages = $config['channels_by_name']['community']['packages'];
        foreach($packages as $package)
        {
            $return[] = $package['name'];
        }
        return $return;
    }

    protected function _getBasePackages()
    {
        return array(
            'Cm_RedisSession',
            'Interface_Adminhtml_Default',
            'Interface_Frontend_Base_Default',
            'Interface_Frontend_Default',
            'Interface_Frontend_Rwd_Default',
            'Interface_Install_Default',
            'Lib_Cm',
            'Lib_Credis',
            'Lib_Google_Checkout',
            'Lib_Js_Calendar',
            'Lib_Js_Ext',
            'Lib_Js_Mage',
            'Lib_Js_Prototype',
            'Lib_Js_TinyMCE',
            'Lib_LinLibertineFont',
            'Lib_Mage',
            'Lib_Magento',
            'Lib_Phpseclib',
            'Lib_Varien',
            'Lib_ZF',
            'Lib_ZF_Locale',
            'Mage_All_Latest',
            'Mage_Centinel',
            'Mage_Compiler',
            'Mage_Core_Adminhtml',
            'Mage_Core_Modules',
            'Mage_Downloader',
            'Mage_Locale_de_DE',
            'Mage_Locale_en_US',
            'Mage_Locale_es_ES',
            'Mage_Locale_fr_FR',
            'Mage_Locale_nl_NL',
            'Mage_Locale_pt_BR',
            'Mage_Locale_zh_CN',
            'Magento_Mobile',
            'Phoenix_Moneybookers',    
        );
    }
    protected function _init($output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }        
    }
        //bootstrap magento
//         

    
}
