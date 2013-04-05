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
        'helpers',
        'models',
        'resourceModels'
    );

    protected $groupFactories = array(
        'models' => array(
            '\Mage::getModel',
            '\Mage::getSingleton',
        ),
        'resourceModels' => array(
            '\Mage::getResourceModel',
            '\Mage::getResourceSingleton',
        ),
        'helpers' => array(
            '\Mage::helper'
       ),
    );

    protected function configure()
    {
        $this
            ->setName('dev:ide:phpstorm:meta')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print to stdout instad of file .phpstorm.meta.php')
            ->setDescription('Generates meta data file for PhpStorm auto completion')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $package
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_1) {

                $classMaps = array();
                foreach ($this->groups as $group) {
                    $classMaps[$group] = $this->getClassMapForGroup($group);
                }

                $map = <<<PHP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    \$STATIC_METHOD_TYPES = [
PHP;
                $map .= "\n";
                foreach ($this->groupFactories as $group => $methods) {
                    foreach ($methods as $method) {
                        $map .= "        " . $method. "('') => [\n";
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
                    \file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php', $map);
                }

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
     * @param SplFileInfo $file
     * @param $classPrefix
     * @return string
     */
    protected function getClassPrefix(SplFileInfo $file, $classPrefix)
    {
        $path = str_replace('.php', '', $file->getRelativePathname());
        $parts = explode('/', $path);
        $parts = array_map('lcfirst', $parts);

        return rtrim($classPrefix . '/' . implode('_', $parts), '/');
    }

    /**
     * @param string $group
     * @return array
     */
    protected function getClassMapForGroup($group)
    {
        $classes = array();
        foreach (\Mage::getConfig()->getNode('global/' . ($group == 'resourceModels' ? 'models' : $group))->children() as $prefix => $modelDefinition) {

            // Handle not existing "class" entry for "core" module.
            if ($prefix == 'core' && $group == 'helpers') {
                $modelDefinition->class = 'Mage_Core_Helper';
            }

            if (empty($modelDefinition->class)) {
                continue;
            }
            if ($group == 'resourceModels') {
                if (empty($modelDefinition->resourceModel)) {
                    continue;
                }
                $resourceModelNodePath = 'global/models/' . strval($modelDefinition->resourceModel);
                $resouceModelConfig = \Mage::getConfig()->getNode($resourceModelNodePath);
                if (empty($resouceModelConfig->class)) {
                    continue;
                }
                $classBaseFolder = str_replace('_', '/', $resouceModelConfig->class);
            } else {
                $classBaseFolder = str_replace('_', '/', $modelDefinition->class);
            }
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
                ->notName('mysql4-*');
            foreach ($finder as $file) {
                $classPrefix = $this->getClassPrefix($file, $prefix);
                if ($group != 'helpers' && strpos($classPrefix, '/') === false) {
                    continue;
                }

                $realClass = $this->getRealClassname($file, $group == 'resourceModels' ? $resouceModelConfig->class : $modelDefinition->class);
                $classes[$classPrefix] = $realClass;
            }
        }

        return $classes;
    }
}