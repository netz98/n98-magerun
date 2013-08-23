<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InfoCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:info')
            ->setDescription('Prints infos about the current magento system.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Magento System Information');

        $this->initMagento();

        $this->infos['Version'] = \Mage::getVersion();
        $this->infos['Edition'] = ($this->_magentoEnterprise ? 'Enterprise' : 'Community');

        $config = \Mage::app()->getConfig();
        $this->_addCacheInfos();

        $this->infos['Session'] = $config->getNode('global/session_save');

        $this->infos['Crypt Key'] = $config->getNode('global/crypt/key');
        $this->infos['Install Date'] = $config->getNode('global/install/date');
        $this->findCoreOverwrites();
        $this->findVendors();

        $table = array();
        foreach ($this->infos as $key => $value) {
            $table[] = array($key, $value);
        }

        $this->getHelper('table')
            ->setHeaders(array('name', 'value'))
            ->setRows($table)
            ->render($output);
    }

    protected function _addCacheInfos()
    {
        $this->infos['Cache Backend'] = get_class(\Mage::app()->getCache()->getBackend());

        switch (get_class(\Mage::app()->getCache()->getBackend())) {
            case 'Zend_Cache_Backend_File':
                $cacheDir = \Mage::app()->getConfig()->getOptions()->getCacheDir();
                $this->infos['Cache Directory'] = $cacheDir;
                break;

            default:
        }
    }

    /**
     * @return int|void
     */
    protected function findCoreOverwrites()
    {
        $folders = array(
            $this->_magentoRootFolder . '/app/code/local/Mage',
            $this->_magentoRootFolder . '/app/code/local/Enterprise',
            $this->_magentoRootFolder . '/app/code/community/Mage',
            $this->_magentoRootFolder . '/app/code/community/Enterprise',
        );
        foreach ($folders as $key => $folder) {
            if (!is_dir($folder)) {
                unset($folders[$key]);
            }
        }

        if (count($folders) > 0) {
            $finder = new Finder();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->in($folders);
            $this->infos['Core Autoloader Overwrites'] = $finder->count();
        }
    }

    /**
     * @return int|void
     */
    protected function findVendors()
    {
        $codePools = array(
            'core'      => $this->_magentoRootFolder . '/app/code/core/',
            'community' => $this->_magentoRootFolder . '/app/code/community/',
        );

        if (is_dir($this->_magentoRootFolder . '/app/code/local/')) {
            $codePools['local'] = $this->_magentoRootFolder . '/app/code/local/';
        }

        foreach ($codePools as $codePool => $codePoolDir) {
            $finder = new Finder();
            $finder
                ->directories()
                ->ignoreUnreadableDirs(true)
                ->in($codePoolDir)
                ->depth(0)
                ->sortByName();

            $vendors = iterator_to_array($finder);
            $vendors = array_map(function($value) use ($codePoolDir) {
                    return str_replace($codePoolDir, '', $value);
                }, $vendors);

            $this->infos['Vendors (' . $codePool . ')'] = implode(', ', $vendors);
        }
    }
}