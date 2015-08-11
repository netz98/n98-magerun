<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class MagentoHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $_magentoRootFolder = null;

    /**
     * @var string
     */
    protected $_magentoMajorVersion = \N98\Magento\Application::MAGENTO_MAJOR_VERSION_1;

    /**
     * @var bool
     */
    protected $_magentoEnterprise = false;

    /**
     * @var bool
     */
    protected $_magerunStopFileFound = false;

    /**
     * @var string
     */
    protected $_magerunStopFileFolder = null;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'magento';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Start Magento detection
     *
     * @param string $folder
     * @param array $subFolders Sub-folders to check
     * @return bool
     */
    public function detect($folder, $subFolders = array())
    {
        $folders = $this->splitPathFolders($folder);
        $folders = $this->checkMagerunFile($folders);
        $folders = $this->checkModman($folders);
        $folders = array_merge($folders, $subFolders);

        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_dir($searchFolder) || !is_readable($searchFolder)) {
                continue;
            }

            $found = $this->_search($searchFolder);
            if ($found) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return string
     */
    public function getRootFolder()
    {
        return $this->_magentoRootFolder;
    }

    public function getEdition()
    {
        return $this->_magentoMajorVersion;
    }

    /**
     * @return bool
     */
    public function isEnterpriseEdition()
    {
        return $this->_magentoEnterprise;
    }

    /**
     * @return mixed
     */
    public function getMajorVersion()
    {
        return $this->_magentoMajorVersion;
    }

    /**
     * @return boolean
     */
    public function isMagerunStopFileFound()
    {
        return $this->_magerunStopFileFound;
    }

    /**
     * @return string
     */
    public function getMagerunStopFileFolder()
    {
        return $this->_magerunStopFileFolder;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    protected function splitPathFolders($folder)
    {
        $folders = array();

        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        foreach ($folderParts as $key => $part) {
            $explodedFolder = implode(DIRECTORY_SEPARATOR, array_slice($folderParts, 0, $key + 1));
            if ($explodedFolder !== '') {
                $folders[] = $explodedFolder;
            }
        }

        return $folders;
    }

    /**
     * Check for modman file and .basedir
     *
     * @param array $folders
     *
     * @return array
     */
    protected function checkModman($folders)
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln('<debug>Folder <info>' . $searchFolder . '</info> is not readable. Skip.</debug>');
                }
                continue;
            }

            $finder = Finder::create();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->depth(0)
                ->followLinks()
                ->ignoreDotFiles(false)
                ->name('.basedir')
                ->in($searchFolder);

            $count = $finder->count();
            if ($count > 0) {
                $baseFolderContent = trim(file_get_contents($searchFolder . DIRECTORY_SEPARATOR . '.basedir'));
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln('<debug>Found modman .basedir file with content <info>' . $baseFolderContent . '</info></debug>');
                }

                if (!empty($baseFolderContent)) {
                    $modmanBaseFolder = $searchFolder
                                      . DIRECTORY_SEPARATOR
                                      . '..'
                                      . DIRECTORY_SEPARATOR
                                      . $baseFolderContent;
                    array_push($folders, $modmanBaseFolder);
                }
            }
        }

        return $folders;
    }

    /**
     * Check for .n98-magerun file
     *
     * @param array $folders
     *
     * @return array
     */
    protected function checkMagerunFile($folders)
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln('<debug>Folder <info>' . $searchFolder . '</info> is not readable. Skip.</debug>');
                }
                continue;
            }
            $finder = Finder::create();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->depth(0)
                ->followLinks()
                ->ignoreDotFiles(false)
                ->name('.n98-magerun')
                ->in($searchFolder);

            $count = $finder->count();
            if ($count > 0) {
                $this->_magerunStopFileFound = true;
                $this->_magerunStopFileFolder = $searchFolder;
                $magerunFileContent = trim(file_get_contents($searchFolder . DIRECTORY_SEPARATOR . '.n98-magerun'));
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln('<debug>Found .n98-magerun file with content <info>' . $magerunFileContent . '</info></debug>');
                }

                $modmanBaseFolder = $searchFolder
                    . DIRECTORY_SEPARATOR
                    . $magerunFileContent;
                array_push($folders, $modmanBaseFolder);
            }
        }

        return $folders;
    }

    /**
     * @param string $searchFolder
     *
     * @return bool
     */
    protected function _search($searchFolder)
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
            $this->output->writeln('<debug>Search for Magento in folder <info>' . $searchFolder . '</info></debug>');
        }

        if (!is_dir($searchFolder . '/app')) {
            return false;
        }

        $finder = Finder::create();
        $finder
            ->ignoreUnreadableDirs(true)
            ->depth(0)
            ->followLinks()
            ->name('Mage.php')
            ->name('bootstrap.php')
            ->name('autoload.php')
            ->in($searchFolder . '/app');

        if ($finder->count() > 0) {
            $files = iterator_to_array($finder, false);
            /* @var $file \SplFileInfo */
            
            $hasMageFile = false;
            foreach ($files as $file) {
                if ($file->getFilename() == 'Mage.php') {
                    $hasMageFile = true;
                }
            }

            $this->_magentoRootFolder = $searchFolder;

            // Magento 2 does not have a god class and thus if this file is not there it is version 2
            if ($hasMageFile == false) {
                $this->_magentoMajorVersion = \N98\Magento\Application::MAGENTO_MAJOR_VERSION_2;
                return true;    // the rest of this does not matter since we are simply exiting with a notice
            }

            if (is_callable(array('\Mage', 'getEdition'))) {
                $this->_magentoEnterprise = (\Mage::getEdition() == 'Enterprise');
            } else {
                $this->_magentoEnterprise = is_dir($this->_magentoRootFolder . '/app/code/core/Enterprise');
            }

            if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                $this->output->writeln('<debug>Found Magento in folder <info>' . $this->_magentoRootFolder . '</info></debug>');
            }

            return true;
        }

        return false;
    }
}
