<?php

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use UnexpectedValueException;
use Varien_Simplexml_Element;

class MetaCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $groups = array(
        'blocks',
        'helpers',
        'models',
        'resource models',
        'resource helpers',
    );

    /**
     * List of supported static factory methods
     *
     * @var array
     */
    protected $groupFactories = array(
        'blocks' => array(
            '\Mage::getBlockSingleton',
        ),
        'helpers' => array(
            '\Mage::helper',
        ),
        'models' => array(
            '\Mage::getModel',
            '\Mage::getSingleton',
        ),
        'resource helpers' => array(
            '\Mage::getResourceHelper',
        ),
        'resource models' => array(
            '\Mage::getResourceModel',
            '\Mage::getResourceSingleton',
        ),
    );

    /**
     * List of supported helper methods
     *
     * @var array
     */
    protected $methodFactories = array(
        'blocks' => array(
            '\Mage_Core_Model_Layout::createBlock',
        ),
        'helpers' => array(
            '\Mage_Admin_Model_User::_getHelper',
            '\Mage_Adminhtml_Controller_Rss_Abstract::_getHelper',
            '\Mage_Adminhtml_Tax_RuleController::_getHelperModel',
            '\Mage_Api_Model_User::_getHelper',
            '\Mage_Bundle_Model_Product_Price::_getHelperData',
            '\Mage_Core_Block_Abstract::helper',
            '\Mage_Core_Model_App::getHelper',
            '\Mage_Core_Model_Factory::getHelper',
            '\Mage_Core_Model_Layout::helper',
            '\Mage_Customer_AccountController::_getHelper',
            '\Mage_Customer_Model_Customer::_getHelper',
            '\Mage_ImportExport_Model_Import_Entity_Product::getHelper',
            '\Mage_Rss_Controller_Abstract::_getHelper',
            '\Mage_SalesRule_Model_Validator::_getHelper',
            '\Mage_Weee_Helper_Data::_getHelper',
            '\Mage_Weee_Model_Config_Source_Fpt_Tax::_getHelper',
        ),
        'models' => array(
            '\Mage_Adminhtml_Tax_RuleController::_getSingletonModel',
            '\Mage_Catalog_Block_Product_Abstract::_getSingletonModel',
            '\Mage_Checkout_Helper_Cart::_getSingletonModel',
            '\Mage_Core_Model_Factory::getModel',
            '\Mage_Core_Model_Factory::getSingleton',
            '\Mage_Customer_AccountController::_getModel',
            '\Mage_SalesRule_Model_Validator::_getSingleton',
            '\Mage_Shipping_Model_Carrier_Tablerate::_getModel',
            '\Mage_Wishlist_Helper_Data::_getSingletonModel',
        ),
        'resource models' => array(
            '\Mage_Core_Model_Factory::getResourceModel',
        ),
    );

    /**
     * @var array
     */
    protected $missingHelperDefinitionModules = array(
        'Backup',
        'Bundle',
        'Captcha',
        'Catalog',
        'Centinel',
        'Checkout',
        'Cms',
        'Core',
        'Customer',
        'Dataflow',
        'Directory',
        'Downloadable',
        'Eav',
        'Index',
        'Install',
        'Log',
        'Media',
        'Newsletter',
        'Page',
        'Payment',
        'Paypal',
        'Persistent',
        'Poll',
        'Rating',
        'Reports',
        'Review',
        'Rss',
        'Rule',
        'Sales',
        'Shipping',
        'Sitemap',
        'Tag',
        'Tax',
        'Usa',
        'Weee',
        'Widget',
        'Wishlist',
    );

    const VERSION_OLD = 'old';
    const VERSION_2017 = '2016.2+';

    protected function configure()
    {
        $this
            ->setName('dev:ide:phpstorm:meta')
            ->addOption(
                'meta-version',
                null,
                InputOption::VALUE_REQUIRED,
                'PhpStorm Meta version (' . self::VERSION_OLD . ', ' . self::VERSION_2017 . ')',
                self::VERSION_2017
            )
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print to stdout instead of file .phpstorm.meta.php')
            ->setDescription('Generates meta data file for PhpStorm auto completion (default version : ' . self::VERSION_2017 . ')');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @internal param string $package
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_1) {
            $classMaps = array();

            foreach ($this->groups as $group) {
                $classMaps[$group] = $this->getClassMapForGroup($group, $output);

                if (!$input->getOption('stdout') && count($classMaps[$group]) > 0) {
                    $output->writeln(
                        '<info>Generated definitions for <comment>' . $group . '</comment> group</info>'
                    );
                }
            }

            $version = $input->getOption('meta-version');
            if ($version == self::VERSION_OLD) {
                $this->writeToOutputOld($input, $output, $classMaps);
            } elseif ($version == self::VERSION_2017) {
                $this->writeToOutputV2017($input, $output, $classMaps);
            }
        } else {
            $output->write('Magento 2 is currently not supported');
        }
    }

    /**
     * @param SplFileInfo $file
     * @param string $classPrefix
     * @return string
     */
    protected function getRealClassname(SplFileInfo $file, $classPrefix)
    {
        $path = $file->getRelativePathname();
        if (substr($path, -4) !== '.php') {
            throw new UnexpectedValueException(
                sprintf('Expected that relative file %s ends with ".php"', var_export($path, true))
            );
        }
        $path = substr($path, 0, -4);
        $path = strtr($path, '\\', '/');

        return trim($classPrefix . '_' . strtr($path, '/', '_'), '_');
    }

    /**
     * @param SplFileInfo   $file
     * @param string        $classPrefix
     * @param string        $group
     * @return string
     */
    protected function getClassIdentifier(SplFileInfo $file, $classPrefix, $group = '')
    {
        $path = str_replace('.php', '', $file->getRelativePathname());
        $path = str_replace('\\', '/', $path);
        $parts = explode('/', $path);
        $parts = array_map('lcfirst', $parts);
        if ($path == 'Data' && ($group == 'helpers')) {
            array_pop($parts);
        }

        return rtrim($classPrefix . '/' . implode('_', $parts), '/');
    }

    /**
     * Verify whether given class is defined in given file because there is no sense in adding class with incorrect
     * file or path. Examples:
     * app/code/core/Mage/Core/Model/Mysql4/Design/Theme/Collection.php -> Mage_Core_Model_Mysql4_Design_Theme
     * app/code/core/Mage/Payment/Model/Paygate/Request.php             -> Mage_Paygate_Model_Authorizenet_Request
     * app/code/core/Mage/Dataflow/Model/Convert/Iterator.php           -> Mage_Dataflow_Model_Session_Adapter_Iterator
     *
     * @param SplFileInfo     $file
     * @param string          $className
     * @param OutputInterface $output
     * @return bool
     */
    protected function isClassDefinedInFile(SplFileInfo $file, $className, OutputInterface $output)
    {
        try {
            return preg_match("/class\s+{$className}/m", $file->getContents());
        } catch (Exception $e) {
            $output->writeln('<error>File: ' . $file->__toString() . ' | ' . $e->getMessage() . '</error>');
            return false;
        }
    }

    /**
     * Resource helper is always one per module for each db type and uses model alias
     *
     * @return array
     */
    protected function getResourceHelperMap()
    {
        $classes = array();

        if (($this->_magentoEnterprise && version_compare(\Mage::getVersion(), '1.11.2.0', '<='))
            || (!$this->_magentoEnterprise && version_compare(\Mage::getVersion(), '1.6.2.0', '<'))
        ) {
            return $classes;
        }

        $modelAliases = array_keys((array) \Mage::getConfig()->getNode('global/models'));
        foreach ($modelAliases as $modelAlias) {
            $resourceHelper = @\Mage::getResourceHelper($modelAlias);
            if (is_object($resourceHelper)) {
                $classes[$modelAlias] = get_class($resourceHelper);
            }
        }

        return $classes;
    }

    /**
     * @param string $group
     * @param OutputInterface $output
     *
     *@return array
     */
    protected function getClassMapForGroup($group, OutputInterface $output)
    {
        /**
         * Generate resource helper only for Magento >= EE 1.11 or CE 1.6
         */
        if ($group == 'resource helpers') {
            return $this->getResourceHelperMap();
        }

        $classes = array();
        foreach ($this->getGroupXmlDefinition($group) as $prefix => $modelDefinition) {
            if ($group == 'resource models') {
                if (empty($modelDefinition->resourceModel)) {
                    continue;
                }
                $resourceModelNodePath = 'global/models/' . strval($modelDefinition->resourceModel);
                $resourceModelConfig = \Mage::getConfig()->getNode($resourceModelNodePath);
                if ($resourceModelConfig) {
                    $classPrefix = strval($resourceModelConfig->class);
                }
            } else {
                $classPrefix = strval($modelDefinition->class);
            }

            if (empty($classPrefix)) {
                continue;
            }

            $classBaseFolder = str_replace('_', '/', $classPrefix);
            $searchFolders = array(
                \Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $classBaseFolder,
                \Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $classBaseFolder,
                \Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . $classBaseFolder,
            );
            foreach ($searchFolders as $key => $folder) {
                if (!is_dir($folder)) {
                    unset($searchFolders[$key]);
                }
            }

            if (empty($searchFolders)) {
                continue;
            }

            $finder = Finder::create();
            $finder
                ->files()
                ->in($searchFolders)
                ->followLinks()
                ->ignoreUnreadableDirs(true)
                ->name('*.php')
                ->notName('install-*')
                ->notName('upgrade-*')
                ->notName('mysql4-*')
                ->notName('mssql-*')
                ->notName('oracle-*');

            foreach ($finder as $file) {
                $classIdentifier = $this->getClassIdentifier($file, $prefix, $group);
                $classNameByPath = $this->getRealClassname($file, $classPrefix);

                switch ($group) {
                    case 'blocks':
                        $classNameAfterRewrites = \Mage::getConfig()->getBlockClassName($classIdentifier);
                        break;

                    case 'helpers':
                        $classNameAfterRewrites = \Mage::getConfig()->getHelperClassName($classIdentifier);
                        break;

                    case 'models':
                        $classNameAfterRewrites = \Mage::getConfig()->getModelClassName($classIdentifier);
                        break;

                    case 'resource models':
                    default:
                        $classNameAfterRewrites = \Mage::getConfig()->getResourceModelClassName($classIdentifier);
                        break;
                }

                if ($classNameAfterRewrites) {
                    $addToList = true;
                    if ($classNameAfterRewrites === $classNameByPath
                        && !$this->isClassDefinedInFile($file, $classNameByPath, $output)
                    ) {
                        $addToList = false;
                    }

                    if ($addToList) {
                        $classes[$classIdentifier] = $classNameAfterRewrites;

                        if ($group == 'helpers' && strpos($classIdentifier, '/') === false) {
                            $classes[$classIdentifier . '/data'] = $classNameAfterRewrites;
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $classMaps
     */
    protected function writeToOutputOld(InputInterface $input, OutputInterface $output, $classMaps)
    {
        $map = <<<PHP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpLanguageLevelInspection */
    \$STATIC_METHOD_TYPES = [
PHP;
        $map .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            foreach ($methods as $method) {
                $map .= "        " . $method . "('') => [\n";
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '$classPrefix' instanceof \\$class,\n";
                    } else {
                        $output->writeln('<warning>Invalid class name <comment>' . $class . '</comment> ignored</warning>');
                    }
                }
                $map .= "        ], \n";
            }
        }
        $map .= <<<PHP
    ];
}
PHP;
        if ($input->getOption('stdout')) {
            $output->writeln($map);
        } else {
            if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php', $map)) {
                $output->writeln('<info>File <comment>.phpstorm.meta.php</comment> generated</info>');
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $classMaps
     */
    protected function writeToOutputV2017(InputInterface $input, OutputInterface $output, $classMaps)
    {
        $baseMap = <<<PHP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpLanguageLevelInspection */
    \$STATIC_METHOD_TYPES = [
PHP;
        $baseMap .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= "        " . $method . "('') => [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '$classPrefix' instanceof \\$class,\n";
                    } else {
                        $output->writeln('<warning>Invalid class name <comment>' . $class . '</comment> ignored</warning>');
                    }
                }
                $map .= "        ], \n";
            }
            $map .= <<<PHP
    ];
}
PHP;
            if ($input->getOption('stdout')) {
                $output->writeln($map);
            } else {
                $metaPath = $this->_magentoRootFolder . '/.phpstorm.meta.php';
                if (is_file($metaPath)) {
                    if (\unlink($metaPath)) {
                        $output->writeln('<info>Deprecated file <comment>.phpstorm.meta.php</comment> removed</info>');
                    }
                }
                if (!is_dir($metaPath)) {
                    if (\mkdir($metaPath)) {
                        $output->writeln('<info>Directory <comment>.phpstorm.meta.php</comment> created</info>');
                    }
                }
                $group = str_replace(array(' ', '/'), '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '.meta.php</comment> generated</info>');
                }
            }
        }

        $baseMap = <<<PHP
<?php
namespace PHPSTORM_META {
PHP;
        $baseMap .= "\n";
        foreach ($this->methodFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= "    override( " . $method . "(0),\n";
                $map .= "        map( [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '$classPrefix' => \\$class::class,\n";
                    } else {
                        $output->writeln('<warning>Invalid class name <comment>' . $class . '</comment> ignored</warning>');
                    }
                }
                $map .= "        ])\n";
                $map .= "    );\n";
            }
            $map .= <<<PHP
}
PHP;
            if ($input->getOption('stdout')) {
                $output->writeln($map);
            } else {
                $group = str_replace(array(' ', '/'), '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '_methods.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '_methods.meta.php</comment> generated</info>');
                }
            }
        }
    }

    /**
     * @param string $group
     * @return \Mage_Core_Model_Config_Element
     */
    protected function getGroupXmlDefinition($group)
    {
        if ($group == 'resource models') {
            $group = 'models';
        }

        $definitions = \Mage::getConfig()->getNode('global/' . $group);

        switch ($group) {
            case 'blocks':
                $groupClassType = 'Block';
                break;

            case 'helpers':
                $groupClassType = 'Helper';
                break;

            case 'models':
                $groupClassType = 'Model';
                break;

            default:
                return $definitions->children();
        }

        foreach ($this->missingHelperDefinitionModules as $moduleName) {
            $children = new Varien_Simplexml_Element(sprintf("<%s/>", strtolower($moduleName)));
            $children->class = sprintf('Mage_%s_%s', $moduleName, $groupClassType);
            $definitions->appendChild($children);
        }

        return $definitions->children();
    }
}
