<?php

namespace N98\Magento\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
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
            } else {
                require_once $this->_magentoRootFolder . '/app/Mage.php';
            }
            \Mage::app('admin');
            return true;
        }

        return false;
    }

    /**
     * Search for magento root folder
     *
     * @param OutputInterface $output
     * @param bool $silent print debug messages
     */
    public function detectMagento(OutputInterface $output, $silent = true)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $folder = exec('@echo %cd%'); // @TODO not currently tested!!!
        } else {
            $folder = exec('pwd');
        }

        $folders = array();
        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        foreach ($folderParts as $key => $part) {
            $explodedFolder = implode(DIRECTORY_SEPARATOR, array_slice($folderParts, 0, $key + 1));
            if ($explodedFolder !== '') {
                $folders[] = $explodedFolder;
            }
        }

        foreach (array_reverse($folders) as $searchFolder) {
            $finder = new Finder();
            $finder
                ->directories()
                ->depth(0)
                ->followLinks()
                ->name('app')
                ->name('skin')
                ->name('lib')
                ->in($searchFolder);

            if ($finder->count() >= 2) {
                $files = iterator_to_array($finder, false); /* @var $file \SplFileInfo */

                if (count($files) == 2) {
                    // Magento 2 has no skin folder.
                    // @TODO find a better magento 2.x check
                    $this->_magentoMajorVersion = self::MAGENTO_MAJOR_VERSION_2;
                }

                $this->_magentoRootFolder = dirname($files[0]->getRealPath());

                if (is_callable(array('\Mage', 'getEdition'))) {
                    $this->_magentoEnterprise = (\Mage::getEdition() == 'Enterprise');
                } else {
                    $this->_magentoEnterprise = is_dir($this->_magentoRootFolder . '/app/code/core/Enterprise');
                }

                if (!$silent) {
                    $editionString = ($this->_magentoEnterprise ? ' (Enterprise Edition) ' : '');
                    $output->writeln('<info>Found Magento '. $editionString . 'in folder "' . $this->_magentoRootFolder . '"</info>');
                }
                return;
            }
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
}
