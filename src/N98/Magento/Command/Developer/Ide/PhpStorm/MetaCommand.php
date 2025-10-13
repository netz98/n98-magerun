<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use Directory;
use Exception;
use Mage;
use Mage_Core_Model_Config_Element;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use UnexpectedValueException;
use Varien_Simplexml_Element;

/**
 * Create phpStorm meta-files command
 *
 * @package N98\Magento\Command\Developer\Ide\PhpStorm
 */
class MetaCommand extends AbstractMagentoCommand
{
    /**
     * @var string[]
     */
    protected array $groups = ['blocks', 'helpers', 'models', 'resource models', 'resource helpers'];

    /**
     * List of supported static factory methods
     * @var array<string, string[]>
     */
    protected array $groupFactories = ['blocks' => ['\Mage::getBlockSingleton'], 'helpers' => ['\Mage::helper'], 'models' => ['\Mage::getModel', '\Mage::getSingleton'], 'resource helpers' => ['\Mage::getResourceHelper'], 'resource models' => ['\Mage::getResourceModel', '\Mage::getResourceSingleton']];

    /**
     * List of supported helper methods
     * @var array<string, string[]>
     */
    protected array $methodFactories = ['blocks' => ['\Mage_Core_Model_Layout::createBlock'], 'helpers' => ['\Mage_Admin_Model_User::_getHelper', '\Mage_Adminhtml_Controller_Rss_Abstract::_getHelper', '\Mage_Adminhtml_Tax_RuleController::_getHelperModel', '\Mage_Api_Model_User::_getHelper', '\Mage_Bundle_Model_Product_Price::_getHelperData', '\Mage_Core_Block_Abstract::helper', '\Mage_Core_Model_App::getHelper', '\Mage_Core_Model_Factory::getHelper', '\Mage_Core_Model_Layout::helper', '\Mage_Customer_AccountController::_getHelper', '\Mage_Customer_Model_Customer::_getHelper', '\Mage_ImportExport_Model_Import_Entity_Product::getHelper', '\Mage_Rss_Controller_Abstract::_getHelper', '\Mage_SalesRule_Model_Validator::_getHelper', '\Mage_Weee_Helper_Data::_getHelper', '\Mage_Weee_Model_Config_Source_Fpt_Tax::_getHelper'], 'models' => ['\Mage_Adminhtml_Tax_RuleController::_getSingletonModel', '\Mage_Catalog_Block_Product_Abstract::_getSingletonModel', '\Mage_Checkout_Helper_Cart::_getSingletonModel', '\Mage_Core_Model_Factory::getModel', '\Mage_Core_Model_Factory::getSingleton', '\Mage_Customer_AccountController::_getModel', '\Mage_SalesRule_Model_Validator::_getSingleton', '\Mage_Shipping_Model_Carrier_Tablerate::_getModel', '\Mage_Wishlist_Helper_Data::_getSingletonModel'], 'resource models' => ['\Mage_Core_Model_Factory::getResourceModel']];

    /**
     * @var string[]
     */
    protected array $missingHelperDefinitionModules = ['Backup', 'Bundle', 'Captcha', 'Catalog', 'Centinel', 'Checkout', 'Cms', 'Core', 'Customer', 'Dataflow', Directory::class, 'Downloadable', 'Eav', 'Index', 'Install', 'Log', 'Media', 'Newsletter', 'Page', 'Payment', 'Paypal', 'Persistent', 'Poll', 'Rating', 'Reports', 'Review', 'Rss', 'Rule', 'Sales', 'Shipping', 'Sitemap', 'Tag', 'Tax', 'Usa', 'Weee', 'Widget', 'Wishlist'];

    public const VERSION_OLD = 'old';

    public const VERSION_2017 = '2016.2+';

    public const VERSION_2019 = '2019.1+';

    protected function configure(): void
    {
        $this
            ->setName('dev:ide:phpstorm:meta')
            ->addOption(
                'meta-version',
                null,
                InputOption::VALUE_REQUIRED,
                'PhpStorm Meta version (' . self::VERSION_OLD . ', ' . self::VERSION_2017 . ', ' . self::VERSION_2019 . ')',
                self::VERSION_2019,
            )
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print to stdout instead of file .phpstorm.meta.php')
            ->setDescription('Generates meta data file for PhpStorm auto completion (default version : ' . self::VERSION_2019 . ')');
    }

    /**
     *
     * @internal param string $package
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $classMaps = [];

        foreach ($this->groups as $group) {
            $classMaps[$group] = $this->getClassMapForGroup($group, $output);

            if (!$input->getOption('stdout') && $classMaps[$group] !== []) {
                $output->writeln(
                    '<info>Generated definitions for <comment>' . $group . '</comment> group</info>',
                );
            }
        }

        $version = $input->getOption('meta-version');
        if ($version == self::VERSION_OLD) {
            $this->writeToOutputOld($input, $output, $classMaps);
        } elseif ($version == self::VERSION_2017) {
            $this->writeToOutputV2017($input, $output, $classMaps);
        } elseif ($version == self::VERSION_2019) {
            $this->writeToOutputV2019($input, $output, $classMaps);
        }

        return Command::SUCCESS;
    }

    protected function getRealClassname(SplFileInfo $file, string $classPrefix): string
    {
        $path = $file->getRelativePathname();
        if (substr($path, -4) !== '.php') {
            throw new UnexpectedValueException(
                sprintf('Expected that relative file %s ends with ".php"', var_export($path, true)),
            );
        }

        $path = substr($path, 0, -4);
        $path = strtr($path, '\\', '/');

        return trim($classPrefix . '_' . strtr($path, '/', '_'), '_');
    }

    protected function getClassIdentifier(SplFileInfo $file, string $classPrefix, string $group = ''): string
    {
        $path = str_replace('.php', '', $file->getRelativePathname());
        $path = str_replace('\\', '/', $path);

        $parts = explode('/', $path);
        $parts = array_map('lcfirst', $parts);
        if ($path == 'Data' && ($group === 'helpers')) {
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
     * @return false|int
     */
    protected function isClassDefinedInFile(SplFileInfo $file, string $className, OutputInterface $output)
    {
        try {
            return preg_match(sprintf('/class\s+%s/m', $className), $file->getContents());
        } catch (Exception $exception) {
            $output->writeln('<error>File: ' . $file->__toString() . ' | ' . $exception->getMessage() . '</error>');
            return false;
        }
    }

    /**
     * Resource helper is always one per module for each db type and uses model alias
     */
    protected function getResourceHelperMap(): array
    {
        $classes = [];

        if (($this->_magentoEnterprise && version_compare(Mage::getVersion(), '1.11.2.0', '<='))
            || (!$this->_magentoEnterprise && version_compare(Mage::getVersion(), '1.6.2.0', '<'))
        ) {
            return $classes;
        }

        $modelAliasesNode = Mage::getConfig()->getNode('global/models');
        /** @var string[] $modelAliases */
        $modelAliases = array_keys((array) $modelAliasesNode);
        foreach ($modelAliases as $modelAlias) {
            $resourceHelper = @Mage::getResourceHelper($modelAlias);
            if (is_object($resourceHelper)) {
                $classes[$modelAlias] = get_class($resourceHelper);
            }
        }

        return $classes;
    }

    protected function getClassMapForGroup(string $group, OutputInterface $output): array
    {
        /**
         * Generate resource helper only for Magento >= EE 1.11 or CE 1.6
         */
        if ($group === 'resource helpers') {
            return $this->getResourceHelperMap();
        }

        $classes        = [];
        $classPrefix    = '';
        foreach ($this->getGroupXmlDefinition($group) as $prefix => $varienSimplexmlElement) {
            if ($group === 'resource models') {
                if (empty($varienSimplexmlElement->resourceModel)) {
                    continue;
                }

                $resourceModelNodePath = 'global/models/' . $varienSimplexmlElement->resourceModel;
                $resourceModelConfig = Mage::getConfig()->getNode($resourceModelNodePath);
                if ($resourceModelConfig) {
                    $classPrefix = (string) ($resourceModelConfig->class);
                }
            } else {
                $classPrefix = (string) ($varienSimplexmlElement->class);
            }

            if ($classPrefix === '') {
                continue;
            }

            if ($classPrefix === '0') {
                continue;
            }

            $classBaseFolder = str_replace('_', '/', $classPrefix);
            $searchFolders = [Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $classBaseFolder, Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $classBaseFolder, Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . $classBaseFolder];
            foreach ($searchFolders as $key => $folder) {
                if (!is_dir($folder)) {
                    unset($searchFolders[$key]);
                }
            }

            if ($searchFolders === []) {
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
                        $classNameAfterRewrites = Mage::getConfig()->getBlockClassName($classIdentifier);
                        break;

                    case 'helpers':
                        $classNameAfterRewrites = Mage::getConfig()->getHelperClassName($classIdentifier);
                        break;

                    case 'models':
                        $classNameAfterRewrites = Mage::getConfig()->getModelClassName($classIdentifier);
                        break;

                    case 'resource models':
                    default:
                        $classNameAfterRewrites = Mage::getConfig()->getResourceModelClassName($classIdentifier);
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

                        if ($group === 'helpers' && strpos($classIdentifier, '/') === false) {
                            $classes[$classIdentifier . '/data'] = $classNameAfterRewrites;
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * @param mixed[][] $classMaps
     */
    protected function writeToOutputOld(InputInterface $input, OutputInterface $output, array $classMaps): void
    {
        $map = <<<PHP_WRAP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpLanguageLevelInspection */
    \$STATIC_METHOD_TYPES = [
PHP_WRAP;
        $map .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            foreach ($methods as $method) {
                $map .= '        ' . $method . "('') => [\n";
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '{$classPrefix}' instanceof \\{$class},\n";
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
        } elseif (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php', $map)) {
            $output->writeln('<info>File <comment>.phpstorm.meta.php</comment> generated</info>');
        }
    }

    /**
     * @param mixed[][] $classMaps
     */
    protected function writeToOutputV2017(InputInterface $input, OutputInterface $output, array $classMaps): void
    {
        $baseMap = <<<PHP_WRAP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpLanguageLevelInspection */
    \$STATIC_METHOD_TYPES = [
PHP_WRAP;
        $baseMap .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= '        ' . $method . "('') => [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '{$classPrefix}' instanceof \\{$class},\n";
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
                if (is_file($metaPath) && \unlink($metaPath)) {
                    $output->writeln('<info>Deprecated file <comment>.phpstorm.meta.php</comment> removed</info>');
                }

                if (!is_dir($metaPath) && \mkdir($metaPath)) {
                    $output->writeln('<info>Directory <comment>.phpstorm.meta.php</comment> created</info>');
                }

                $group = str_replace([' ', '/'], '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '.meta.php</comment> generated</info>');
                }
            }
        }

        $baseMap = <<<PHP_WRAP
<?php
namespace PHPSTORM_META {
PHP_WRAP;
        $baseMap .= "\n";
        foreach ($this->methodFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= '    override( ' . $method . "(0),\n";
                $map .= "        map( [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '{$classPrefix}' => \\{$class}::class,\n";
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
                $group = str_replace([' ', '/'], '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '_methods.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '_methods.meta.php</comment> generated</info>');
                }
            }
        }
    }

    /**
     * @param mixed[][] $classMaps
     */
    protected function writeToOutputV2019(InputInterface $input, OutputInterface $output, array $classMaps): void
    {
        $baseMap = <<<PHP_WRAP
<?php
namespace PHPSTORM_META {
PHP_WRAP;
        $baseMap .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= '    override( ' . $method . "(0),\n";
                $map .= "        map( [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '{$classPrefix}' => \\{$class}::class,\n";
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
                $metaPath = $this->_magentoRootFolder . '/.phpstorm.meta.php';
                if (is_file($metaPath) && \unlink($metaPath)) {
                    $output->writeln('<info>Deprecated file <comment>.phpstorm.meta.php</comment> removed</info>');
                }

                if (!is_dir($metaPath) && \mkdir($metaPath)) {
                    $output->writeln('<info>Directory <comment>.phpstorm.meta.php</comment> created</info>');
                }

                $group = str_replace([' ', '/'], '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '.meta.php</comment> generated</info>');
                }
            }
        }

        $baseMap = <<<PHP_WRAP
<?php
namespace PHPSTORM_META {
PHP_WRAP;
        $baseMap .= "\n";
        foreach ($this->methodFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= '    override( ' . $method . "(0),\n";
                $map .= "        map( [\n";
                asort($classMaps[$group]);
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '{$classPrefix}' => \\{$class}::class,\n";
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
                $group = str_replace([' ', '/'], '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_' . $group . '_methods.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_' . $group . '_methods.meta.php</comment> generated</info>');
                }
            }
        }
    }

    protected function getGroupXmlDefinition(string $group): ?Varien_Simplexml_Element
    {
        if ($group === 'resource models') {
            $group = 'models';
        }

        /** @var Mage_Core_Model_Config_Element $definitions */
        $definitions = Mage::getConfig()->getNode('global/' . $group);

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

        foreach ($this->missingHelperDefinitionModules as $missingHelperDefinitionModule) {
            $children = new Varien_Simplexml_Element(sprintf('<%s/>', strtolower($missingHelperDefinitionModule)));
            $children->class = sprintf('Mage_%s_%s', $missingHelperDefinitionModule, $groupClassType);
            $definitions->appendChild($children);
        }

        return $definitions->children();
    }
}
