<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Finder\Finder;

class MagentoHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $_magentoRootFolder = null;

    /**
     * @var
     */
    protected $_magentoMajorVersion = \N98\Magento\Application::MAGENTO_MAJOR_VERSION_1;

    /**
     * @var bool
     */
    protected $_magentoEnterprise = false;

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
     * Start Magento detection
     *
     * @param string $folder
     * @param array $subFolders Sub-folders to check
    */
    public function detect($folder, $subFolders = array())
    {
        $folders = $this->splitPathFolders($folder);
        $folders = $this->checkModman($folders);
        $folders = array_merge($folders, $subFolders);

        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_dir($searchFolder) || !is_readable($searchFolder)) {
                continue;
            }
            if ($this->_search($searchFolder)) {
                break;
            }
        }
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
     * @param $folder
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
     * @param $folders
     *
     * @return array
     */
    protected function checkModman($folders)
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
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
                if (!empty($baseFolderContent)) {
                    $modmanBaseFolder = $searchFolder
                        . DIRECTORY_SEPARATOR
                        . '..'
                        . DIRECTORY_SEPARATOR
                        . trim($baseFolderContent);
                    array_push($folders, $modmanBaseFolder);
                }
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

            if (count($files) == 2) {
                // Magento 2 has bootstrap.php and autoload.php in app folder
                $this->_magentoMajorVersion = \N98\Magento\Application::MAGENTO_MAJOR_VERSION_2;
            }

            $this->_magentoRootFolder = $searchFolder;

            if (is_callable(array('\Mage', 'getEdition'))) {
                $this->_magentoEnterprise = (\Mage::getEdition() == 'Enterprise');
            } else {
                $this->_magentoEnterprise = is_dir($this->_magentoRootFolder . '/app/code/core/Enterprise');
            }

            return true;
        }

        return false;
    }
}
