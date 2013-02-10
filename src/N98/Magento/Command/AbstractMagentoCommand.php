<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;

abstract class AbstractMagentoCommand extends Command
{
    /**
     * @var int
     */
    const MAGENTO_MAJOR_VERSION_1 = 1;

    /**
     * @var int
     */
    const MAGENTO_MAJOR_VERSION_2 = 2;

    /**
     * @var string
     */
    protected $_magentoRootFolder = null;

    /**
     * @var int
     */
    protected $_magentoMajorVersion = self::MAGENTO_MAJOR_VERSION_1;

    /**
     * @var bool
     */
    protected $_magentoEnterprise = false;

    /**
     * @var array
     */
    protected $_deprecatedAlias = array();

    /**
     * @var array
     */
    protected $_websiteCodeMap = array();

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->checkDeprecatedAliases($input, $output);
    }

    private function _initWebsites()
    {
        $this->_websiteCodeMap = array();
        $websites = \Mage::app()->getWebsites(false);
        foreach ($websites as $website) {
            $this->_websiteCodeMap[$website->getId()] = $website->getCode();
        }
    }

    /**
     * @param int $websiteId
     * @return string
     */
    protected function _getWebsiteCodeById($websiteId)
    {
        if (empty($this->_websiteCodeMap)) {
            $this->_initWebsites();
        }

        return $this->_websiteCodeMap[$websiteId];
    }

    /**
     * @param string $websiteCode
     * @return int
     */
    protected function _getWebsiteIdByCode($websiteCode)
    {
        if (empty($this->_websiteCodeMap)) {
            $this->_initWebsites();
        }
        $websiteMap = array_flip($this->_websiteCodeMap);

        return $websiteMap[$websiteCode];
    }

    /**
     * @return array
     */
    protected function getCommandConfig()
    {
        $configArray = $this->getApplication()->getConfig();
        if (isset($configArray['commands'][get_class($this)])) {
            return $configArray['commands'][get_class($this)];
        }

        return null;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $text
     * @param string $style
     */
    protected function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }

    /**
     * Bootstrap magento shop
     *
     * @return bool
     */
    protected function initMagento()
    {
        if ($this->_magentoRootFolder !== null) {
            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
                require_once $this->_magentoRootFolder . '/app/bootstrap.php';
                if (version_compare(\Mage::getVersion(), '2.0.0.0-dev41') >= 0) {
                    \Mage::app(array('MAGE_RUN_CODE' => 'admin'));
                } else {
                    \Mage::app('admin');
                }
            } else {
                require_once $this->_magentoRootFolder . '/app/Mage.php';
                \Mage::app('admin');
            }
            return true;
        }

        return false;
    }

    /**
     * Search for magento root folder
     *
     * @param OutputInterface $output
     * @param bool $silent print debug messages
     * @throws \RuntimeException
     */
    public function detectMagento(OutputInterface $output, $silent = true)
    {
        $this->getApplication()->detectMagento();

        $this->_magentoEnterprise = $this->getApplication()->isMagentoEnterprise();
        $this->_magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $this->_magentoMajorVersion = $this->getApplication()->getMagentoMajorVersion();

        if (!$silent) {
            $editionString = ($this->_magentoEnterprise ? ' (Enterprise Edition) ' : '');
            $output->writeln('<info>Found Magento '. $editionString . 'in folder "' . $this->_magentoRootFolder . '"</info>');
        }

        if (!empty($this->_magentoRootFolder)) {
            return;
        }

        throw new \RuntimeException('Magento folder could not be detected');
    }

    /**
     * Die if not Enterprise
     */
    protected function requireEnterprise(OutputInterface $output)
    {
        if (!$this->_magentoEnterprise) {
            $output->writeln('<error>Enterprise Edition is required but was not detected</error>');
            exit;
        }
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function getCoreHelper()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::helper('Mage_Core_Helper_Data');
        }
        return \Mage::helper('core');
    }

    /**
     * @param Input Interface $input
     * @param OutputInterface $output
     * @return \Composer\Downloader\DownloadManager
     */
    protected function getComposerDownloadManager($input, $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $composer = ComposerFactory::create($io, array());
        return $composer->getDownloadManager();
    }

    /**
     * @return \Composer\Package\MemoryPackage
     */
    protected function createComposerPackageByConfig($config)
    {
        $packageLoader = new PackageLoader();
        return $package = $packageLoader->load($config);
    }

    /**
     * @param Input Interface $input
     * @param OutputInterface $output
     * @param array|\Composer\Package\PackageInterface $config
     * @param string $targetFolder
     * @param bool $preferSource
     * @return \Composer\Package\MemoryPackage
     */
    protected function downloadByComposerConfig($input, $output, $config, $targetFolder, $preferSource = true)
    {
        $dm = $this->getComposerDownloadManager($input, $output);
        if (! $config instanceof \Composer\Package\PackageInterface) {
            $package = $this->createComposerPackageByConfig($config);
        } else {
            $package = $config;
        }
        $dm->download($package, $targetFolder, $preferSource);
        return $package;
    }

    /**
     * @param string $alias
     * @param string $message
     * @return AbstractMagentoCommand
     */
    protected function addDeprecatedAlias($alias, $message)
    {
        $this->_deprecatedAlias[$alias] = $message;

        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function checkDeprecatedAliases(InputInterface $input, OutputInterface $output)
    {
        if (isset($this->_deprecatedAlias[$input->getArgument('command')])) {
            $output->writeln('<error>Deprecated:</error> <comment>' . $this->_deprecatedAlias[$input->getArgument('command')] . '</comment>');
        }
    }

    /**
     * Magento 1 / 2 switches
     *
     * @param $mage1code string Magento 1 class code
     * @param $mage2class string Magento 2 class name
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getModel($mage1code, $mage2class)
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel($mage2class);
        } else {
            return \Mage::getModel($mage1code);
        }
    }

    /**
     * Magento 1 / 2 switches
     *
     * @param $mage1code string Magento 1 class code
     * @param $mage2class string Magento 2 class name
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getResourceModel($mage1code, $mage2class)
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getResourceModel($mage2class);
        } else {
            return \Mage::getResourceModel($mage1code);
        }
    }

    /**
     * Magento 1 / 2 switches
     *
     * @param $mage1code string Magento 1 class code
     * @param $mage2class string Magento 2 class name
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getResourceSingleton($mage1code, $mage2class)
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getResourceSingleton($mage2class);
        } else {
            return \Mage::getResourceSingleton($mage1code);
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    protected function _parseBoolOption($value)
    {
        return in_array(strtolower($value), array('y', 'yes', 1, 'true'));
    }
}
