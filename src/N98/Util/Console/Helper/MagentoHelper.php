<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use Mage;
use N98\Magento\Application;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class MagentoHelper
 *
 * @package N98\Util\Console\Helper
 */
class MagentoHelper extends AbstractHelper
{
    protected string $_magentoRootFolder = '';

    protected int $_magentoMajorVersion = 1;

    protected bool $_magentoEnterprise = false;

    protected bool $_magerunStopFileFound = false;

    protected string $_magerunStopFileFolder = '';

    /**
     * @var InputInterface|ArgvInput
     */
    protected $input;

    /**
     * @var OutputInterface|ConsoleOutput
     */
    protected $output;

    protected string $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName(): string
    {
        return 'magento';
    }

    public function __construct(?InputInterface $input = null, ?OutputInterface $output = null)
    {
        if (!$input instanceof InputInterface) {
            $input = new ArgvInput();
        }

        if (!$output instanceof OutputInterface) {
            $output = new ConsoleOutput();
        }

        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Start Magento detection
     *
     * @param array $subFolders [optional] sub-folders to check
     */
    public function detect(string $folder, array $subFolders = []): bool
    {
        $folders = $this->splitPathFolders($folder);
        $folders = $this->checkMagerunFile($folders);
        $folders = $this->checkModman($folders);
        $folders = array_merge($folders, $subFolders);

        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_dir($searchFolder)) {
                continue;
            }

            if (!is_readable($searchFolder)) {
                continue;
            }

            $found = $this->_search($searchFolder);
            if ($found) {
                return true;
            }
        }

        return false;
    }

    public function getRootFolder(): string
    {
        return $this->_magentoRootFolder;
    }

    public function getEdition(): int
    {
        return $this->_magentoMajorVersion;
    }

    public function isEnterpriseEdition(): bool
    {
        return $this->_magentoEnterprise;
    }

    public function getMajorVersion(): int
    {
        return $this->_magentoMajorVersion;
    }

    public function isMagerunStopFileFound(): bool
    {
        return $this->_magerunStopFileFound;
    }

    public function getMagerunStopFileFolder(): ?string
    {
        return $this->_magerunStopFileFolder;
    }

    /**
     * @return string[]
     */
    protected function splitPathFolders(string $folder): array
    {
        $folders = [];

        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        foreach (array_keys($folderParts) as $key) {
            $explodedFolder = implode(DIRECTORY_SEPARATOR, array_slice($folderParts, 0, $key + 1));
            if ($explodedFolder !== '') {
                $folders[] = $explodedFolder;
            }
        }

        return $folders;
    }

    /**
     * Check for modman file and .basedir
     */
    protected function checkModman(array $folders): array
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln(
                        '<debug>Folder <info>' . $searchFolder . '</info> is not readable. Skip.</debug>',
                    );
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
                $baseFolderContent = trim((string) file_get_contents($searchFolder . DIRECTORY_SEPARATOR . '.basedir'));
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln(
                        '<debug>Found modman .basedir file with content <info>' . $baseFolderContent . '</info></debug>',
                    );
                }

                if ($baseFolderContent !== '' && $baseFolderContent !== '0') {
                    $folders[] = $searchFolder . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $baseFolderContent;
                }
            }
        }

        return $folders;
    }

    /**
     * Check for magerun stop-file
     * @param string[] $folders
     */
    protected function checkMagerunFile(array $folders): array
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln(
                        sprintf('<debug>Folder <info>%s</info> is not readable. Skip.</debug>', $searchFolder),
                    );
                }

                continue;
            }

            $stopFile = '.' . pathinfo($this->_customConfigFilename, PATHINFO_FILENAME);
            $finder = Finder::create();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->depth(0)
                ->followLinks()
                ->ignoreDotFiles(false)
                ->name($stopFile)
                ->in($searchFolder);

            $count = $finder->count();
            if ($count > 0) {
                $this->_magerunStopFileFound = true;
                $this->_magerunStopFileFolder = $searchFolder;
                $magerunFilePath = $searchFolder . DIRECTORY_SEPARATOR . $stopFile;
                $magerunFileContent = trim((string) file_get_contents($magerunFilePath));
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $message = sprintf(
                        "<debug>Found stopfile '%s' file with content <info>%s</info></debug>",
                        $stopFile,
                        $magerunFileContent,
                    );
                    $this->output->writeln($message);
                }

                $folders[] = $searchFolder . DIRECTORY_SEPARATOR . $magerunFileContent;
            }
        }

        return $folders;
    }

    protected function _search(string $searchFolder): bool
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
            $this->output->writeln('<debug>Search for Magento in folder <info>' . $searchFolder . '</info></debug>');
        }

        if (!is_dir($searchFolder . '/app')) {
            return false;
        }

        $finder = Finder::create();
        $finder
            ->ignoreUnreadableDirs()
            ->depth(0)
            ->followLinks()
            ->name('Mage.php')
            ->name('bootstrap.php')
            ->name('autoload.php')
            ->in($searchFolder . '/app');

        if ($finder->count() > 0) {
            $files = iterator_to_array($finder, false);
            /* @var \SplFileInfo $file */

            $this->_magentoRootFolder = $searchFolder;

            if (is_callable(['\Mage', 'getEdition'])) {
                $this->_magentoEnterprise = (Mage::getEdition() == 'Enterprise');
            } else {
                $this->_magentoEnterprise = is_dir($this->_magentoRootFolder . '/app/code/core/Enterprise') ||
                    is_dir($this->_magentoRootFolder . '/app/design/frontend/enterprise/default/layout');
            }

            if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                $this->output->writeln(
                    '<debug>Found Magento in folder <info>' . $this->_magentoRootFolder . '</info></debug>',
                );
            }

            return true;
        }

        return false;
    }
}
