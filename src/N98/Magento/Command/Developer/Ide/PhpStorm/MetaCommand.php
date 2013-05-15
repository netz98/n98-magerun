<?php

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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

    protected $groupFactories = array(
        'blocks' => array(
            '\Mage::getBlockSingleton'
        ),
        'helpers' => array(
            '\Mage::helper'
        ),
        'models' => array(
            '\Mage::getModel',
            '\Mage::getSingleton',
        ),
        'resource helpers' => array(
            '\Mage::getResourceHelper'
        ),
        'resource models' => array(
            '\Mage::getResourceModel',
            '\Mage::getResourceSingleton',
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

    protected function configure()
    {
        $this
            ->setName('dev:ide:phpstorm:meta')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print to stdout instead of file .phpstorm.meta.php')
            ->setDescription('Generates meta data file for PhpStorm auto completion')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @internal param string $package
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento($output)) {
            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_1) {
                $classMaps = array();

                foreach ($this->groups as $group) {
                    $classMaps[$group] = $this->getClassMapForGroup($group);

                    if (!$input->getOption('stdout') && count($classMaps[$group]) > 0) {
                        $output->writeln('<info>Generated definitions for <comment>' . $group . '</comment> group</info>');
                    }
                }

                $this->writeToOutput($input, $output, $classMaps);
            } else {
                $output->write('Magento 2 is currently not supported');
            }
        }
    }

    /**
     * @param SplFileInfo $file
     * @param string $classPrefix
     * @return string
     */
    protected function getRealClassname(SplFileInfo $file, $classPrefix)
    {
        $path = str_replace('.php', '', $file->getRelativePathname());

        return trim($classPrefix . '_' . str_replace('/', '_', $path), '_');
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
     * @param SplFileInfo $file
     * @param string $className
     * @return int
     */
    protected function isClassDefinedInFile(SplFileInfo $file, $className)
    {
        return preg_match("/class\s+{$className}/m", $file->getContents());
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
     * @return array
     */
    protected function getClassMapForGroup($group)
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

            $finder = new Finder();
            $finder
                ->files()
                ->in($searchFolders)
                ->followLinks()
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
                        && !$this->isClassDefinedInFile($file, $classNameByPath)
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
    protected function writeToOutput(InputInterface $input, OutputInterface $output, $classMaps)
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
                    $map .= "            '$classPrefix' instanceof \\$class,\n";
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
     * @param $group
     * @return \Mage_Core_Model_Config_Element
     */
    protected function getGroupXmlDefinition($group)
    {
        if ($group == 'resource models') {
            $group = 'models';
        }

        $definitions = \Mage::getConfig()->getNode('global/' . $group);

        if (in_array($group, array('blocks', 'helpers', 'models'))) {
            foreach ($this->missingHelperDefinitionModules as $moduleName) {
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
                }

                $moduleXmlDefinition = '<'. strtolower($moduleName) .'>'
                    . '   <class>Mage_' . $moduleName . '_' . $groupClassType .'</class>'
                    . '</' . strtolower($moduleName). '>';
                $children = new \Varien_Simplexml_Element($moduleXmlDefinition);
                $definitions->appendChild($children);
            }
        }

        return $definitions->children();
    }
}
