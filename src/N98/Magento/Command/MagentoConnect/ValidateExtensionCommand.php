<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Magento\Command\AbstractMagentoCommand;
use SimpleXMLElement;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateExtensionCommand extends AbstractMagentoCommand
{
    protected $_connectConfig = false;

    protected function configure()
    {
        $this
            ->setName('extension:validate')
            ->addArgument('package', InputArgument::OPTIONAL, 'Package_Module to check')
            ->addOption(
                'skip-file',
                null,
                InputOption::VALUE_NONE,
                'If set, command will skip reporting the existence of package files'
            )
            ->addOption(
                'skip-hash',
                null,
                InputOption::VALUE_NONE,
                'If set, command will skip validating the package file hashes'
            )
            ->addOption(
                'full-report',
                null,
                InputOption::VALUE_NONE,
                'If set, command will report on ALL package files'
            )
            ->addOption(
                'include-default',
                null,
                InputOption::VALUE_NONE,
                'Include default packages that ship with Magento Connect'
            )
            ->setDescription('Reads Magento Connect Config, and checks that installed package files are really there');

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
        if ($packages == array(null)) {
            $packages = $this->_getInstalledPackages();
        }

        $to_skip = array();
        if (!$input->getOption('include-default')) {
            $to_skip = $this->_getBasePackages();
        }

        foreach ($packages as $package) {
            if (in_array($package, $to_skip)) {
                continue;
            }

            $output->writeln(
                array(
                    $package,
                    '--------------------------------------------------',
                    '',
                    '',
                )
            );

            $this->_validateSpecificPackage($package, $output, $input);
        }

        $output->writeln('');
    }

    /**
     * @param string $name
     * @return array|bool
     */
    protected function _getSpecificPackageConfig($name)
    {
        $config = $this->_loadConfig();
        $packages = $config['channels_by_name']['community']['packages'];

        return isset($packages[$name]) ? $packages[$name] : false;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function _getExtensionFileListFromSpecificConfig($config)
    {
        $xml = simplexml_load_string($config['xml']);
        $return = array();
        foreach ($xml->contents->children() as $target) {
            $files = $target->xpath('//file');
            $return = array();
            foreach ($files as $file) {
                $path = $this->_getPathOfFileNodeToTarget($file);
                $return[$path] = (string) $file['hash'];
            }
        }

        return $return;
    }

    /**
     * @param string $targetName
     * @return string
     */
    protected function _getBasePathFromTargetName($targetName)
    {
        $paths = array(
            'mageetc'       => 'app/etc',
            'magecommunity' => 'app/code/community',
            'magedesign'    => 'app/design',
            'magelocale'    => 'app/locale',
            'magelocal'     => 'app/code/local',
            'magecore'      => 'app/code/core',
            'magelib'       => 'lib',
            'magemedia'     => 'media',
            'mageskin'      => 'skin',
            'mageweb'       => '.',
            'magetest'      => 'tests',
            'mage'          => '.',
        );

        return $paths[$targetName];
    }

    /**
     * @param SimpleXMLElement $node
     * @param string           $path
     *
     * @return string
     */
    protected function _getPathOfFileNodeToTarget(SimpleXMLElement $node, $path = '')
    {
        if ($node->getName() == 'target') {
            return $this->_getBasePathFromTargetName((string) $node['name']) . $path;
        }

        $path = '/' . $node['name'] . $path;
        $parent = $this->_getParentNode($node);

        return $this->_getPathOfFileNodeToTarget($parent, $path);
    }

    /**
     * @param SimpleXMLElement $node
     * @return mixed
     */
    protected function _getParentNode($node)
    {
        $parent = $node->xpath("..");

        return array_shift($parent);
    }

    /**
     * @param SimpleXmlElement $node
     * @param string $path
     * @return string
     */
    protected function getPathOfFileNodeToTarget($node, $path = '')
    {
        if ($node->getName() == 'target') {
            return $this->_getBasePathFromTargetName((string) $node['name']) . $path;
        }

        $path = '/' . $node['name'] . $path;
        $parent = $this->_getParentNode($node);
        return $this->_getPathOfFileNodeToTarget($parent, $path);
    }

    /**
     * @param string $package
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    protected function _validateSpecificPackage($package, $output, $input)
    {
        $files = array();
        $config = $this->_getSpecificPackageConfig($package);
        if ($config) {
            $files = $this->_getExtensionFileListFromSpecificConfig($config);
        }

        $pathBase = \Mage::getBaseDir();
        foreach ($files as $path => $hash) {
            $path = $pathBase . \DS . $path;
            $this->_optionOutput('Checking: ' . $path, 'full-report', $output, $input);

            if (file_exists($path)) {
                $this->_optionOutput('    Path: OK', array('full-report', 'file'), $output, $input);

                if ("" === $hash) {
                    $this->_optionOutput('    Hash: EMPTY', array('full-report', 'hash'), $output, $input);
                } elseif (md5(file_get_contents($path)) === $hash) {
                    $this->_optionOutput('    Hash: OK', array('full-report', 'hash'), $output, $input);
                } else {
                    $this->_optionOutput('Problem: ' . $path, 'hash', $output, $input);
                    $this->_optionOutput('    Hash: MISMATCH', 'hash', $output, $input);
                }
            } else {
                $this->_optionOutput('Problem: ' . $path, 'file', $output, $input);
                $this->_optionOutput('    Path: FILE NOT FOUND', 'file', $output, $input);
            }
        }
    }

    /**
     * @param string $text
     * @param string $type
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    protected function _optionOutput($text, $type, $output, $input)
    {
        $type = is_array($type) ? $type : array($type);

        $skipHash = $input->getOption('skip-hash');
        $skipFile = $input->getOption('skip-file');
        $fullReport = $input->getOption('full-report');

        if (in_array('full-report', $type) && !$fullReport) {
            return;
        }

        if (in_array('hash', $type) && $skipHash) {
            return;
        }

        if (in_array('file', $type) && $skipFile) {
            return;
        }

        $output->writeln($text);
    }

    /**
     * @return bool|mixed
     */
    protected function _loadConfig()
    {
        if (!$this->_connectConfig) {
            $this->_connectConfig = file_get_contents($this->_getDownloaderConfigPath());
            $this->_connectConfig = gzuncompress($this->_connectConfig);
            $this->_connectConfig = unserialize($this->_connectConfig);
        }

        return $this->_connectConfig;
    }

    /**
     * @return array
     */
    protected function _getInstalledPackages()
    {
        $config = $this->_loadConfig();
        $packages = $config['channels_by_name']['community']['packages'];
        foreach ($packages as $package) {
            $return[] = $package['name'];
        }

        return $return;
    }

    /**
     * @return string[]
     */
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

    /**
     * @param OutputInterface $output
     */
    protected function _init($output)
    {
        $this->detectMagento($output);

        if (!$this->initMagento()) {
            return;
        }
    }

    /**
     * @return string
     */
    protected function _getDownloaderConfigPath()
    {
        return \Mage::getBaseDir() . '/downloader/cache.cfg';
    }
}
