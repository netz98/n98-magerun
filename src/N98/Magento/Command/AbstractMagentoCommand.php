<?php

namespace N98\Magento\Command;

use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\OperatingSystem;
use N98\Util\StringTyped;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractMagentoCommand
 *
 * @package N98\Magento\Command
 *
 * @method \N98\Magento\Application getApplication() getApplication()
 */
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
        /** @var \Mage_Core_Model_Website[] $websites */
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

        if (isset($this->_websiteCodeMap[$websiteId])) {
            return $this->_websiteCodeMap[$websiteId];
        }

        return '';
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
     * @param string|null $commandClass
     * @return array
     */
    protected function getCommandConfig($commandClass = null)
    {
        if (null === $commandClass) {
            $commandClass = get_class($this);
        }

        /** @var \N98\Magento\Application $application */
        $application = $this->getApplication();
        return (array) $application->getConfig('commands', $commandClass);
    }

    /**
     * @param OutputInterface $output
     * @param string $text
     * @param string $style
     */
    protected function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }

    /**
     * Bootstrap magento shop
     *
     * @param bool $soft
     * @return bool
     */
    protected function initMagento($soft = false)
    {
        $application = $this->getApplication();
        $init = $application->initMagento($soft);
        if ($init) {
            $this->_magentoRootFolder = $application->getMagentoRootFolder();
        }

        return $init;
    }

    /**
     * Search for magento root folder
     *
     * @param OutputInterface $output
     * @param bool $silent print debug messages
     * @throws RuntimeException
     */
    public function detectMagento(OutputInterface $output, $silent = true)
    {
        $this->getApplication()->detectMagento();

        $this->_magentoEnterprise = $this->getApplication()->isMagentoEnterprise();
        $this->_magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $this->_magentoMajorVersion = $this->getApplication()->getMagentoMajorVersion();

        if (!$silent) {
            $editionString = ($this->_magentoEnterprise ? ' (Enterprise Edition) ' : '');
            $output->writeln(
                '<info>Found Magento ' . $editionString . 'in folder "' . $this->_magentoRootFolder . '"</info>'
            );
        }

        if (!empty($this->_magentoRootFolder)) {
            return;
        }

        throw new RuntimeException('Magento folder could not be detected');
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
     * @return \Mage_Core_Helper_Data
     */
    protected function getCoreHelper()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::helper('Mage_Core_Helper_Data');
        }
        return \Mage::helper('core');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return \Composer\Downloader\DownloadManager
     */
    protected function getComposerDownloadManager($input, $output)
    {
        return $this->getComposer($input, $output)->getDownloadManager();
    }

    /**
     * @param array|PackageInterface $config
     * @return \Composer\Package\CompletePackage
     */
    protected function createComposerPackageByConfig($config)
    {
        $packageLoader = new PackageLoader();
        return $packageLoader->load($config);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array|PackageInterface $config
     * @param string $targetFolder
     * @param bool $preferSource
     * @return \Composer\Package\CompletePackage
     */
    protected function downloadByComposerConfig(
        InputInterface $input,
        OutputInterface $output,
        $config,
        $targetFolder,
        $preferSource = true
    ) {
        $dm = $this->getComposerDownloadManager($input, $output);
        if (!$config instanceof PackageInterface) {
            $package = $this->createComposerPackageByConfig($config);
        } else {
            $package = $config;
        }

        $helper = new \N98\Util\Console\Helper\MagentoHelper();
        $helper->detect($targetFolder);
        if ($this->isSourceTypeRepository($package->getSourceType()) && $helper->getRootFolder() == $targetFolder) {
            $package->setInstallationSource('source');
            $this->checkRepository($package, $targetFolder);
            $dm->update($package, $package, $targetFolder);
        } else {
            $dm->download($package, $targetFolder, $preferSource);
        }

        return $package;
    }

    /**
     * brings locally cached repository up to date if it is missing the requested tag
     *
     * @param PackageInterface $package
     * @param string $targetFolder
     */
    protected function checkRepository($package, $targetFolder)
    {
        if ($package->getSourceType() == 'git') {
            $command = sprintf(
                'cd %s && git rev-parse refs/tags/%s',
                escapeshellarg($this->normalizePath($targetFolder)),
                escapeshellarg($package->getSourceReference())
            );
            $existingTags = shell_exec($command);
            if (!$existingTags) {
                $command = sprintf('cd %s && git fetch', escapeshellarg($this->normalizePath($targetFolder)));
                shell_exec($command);
            }
        } elseif ($package->getSourceType() == 'hg') {
            $command = sprintf(
                'cd %s && hg log --template "{tags}" -r %s',
                escapeshellarg($targetFolder),
                escapeshellarg($package->getSourceReference())
            );
            $existingTag = shell_exec($command);
            if ($existingTag === $package->getSourceReference()) {
                $command = sprintf('cd %s && hg pull', escapeshellarg($targetFolder));
                shell_exec($command);
            }
        }
    }

    /**
     * normalize paths on windows / cygwin / msysgit
     *
     * when using a path value that has been created in a cygwin shell but then PHP uses it inside a cmd shell it needs
     * to be filtered.
     *
     * @param  string $path
     * @return string
     */
    protected function normalizePath($path)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $path = strtr($path, '/', '\\');
        }
        return $path;
    }

    /**
     * obtain composer
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return \Composer\Composer
     */
    protected function getComposer(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $config = array(
            'config' => array(
                'secure-http' => false,
            ),
        );

        return ComposerFactory::create($io, $config);
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
            $output->writeln(
                '<error>Deprecated:</error> <comment>' . $this->_deprecatedAlias[$input->getArgument('command')] .
                '</comment>'
            );
        }
    }

    /**
     * Magento 1 / 2 switches
     *
     * @param string $mage1code Magento 1 class code
     * @param string $mage2class Magento 2 class name
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
     * @param string $mage1code Magento 1 class code
     * @param string $mage2class Magento 2 class name
     * @return \Mage_Core_Helper_Abstract
     */
    protected function _getHelper($mage1code, $mage2class)
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::helper($mage2class);
        } else {
            return \Mage::helper($mage1code);
        }
    }

    /**
     * Magento 1 / 2 switches
     *
     * @param string $mage1code Magento 1 class code
     * @param string $mage2class Magento 2 class name
     * @return \Mage_Core_Model_Abstract
     */
    protected function _getSingleton($mage1code, $mage2class)
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
     * @param string $mage1code Magento 1 class code
     * @param string $mage2class Magento 2 class name
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
     * @param string $mage1code Magento 1 class code
     * @param string $mage2class Magento 2 class name
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
        return StringTyped::parseBoolOption($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function formatActive($value)
    {
        return StringTyped::formatActive($value);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->getHelperSet()->setCommand($this);

        return parent::run($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function chooseInstallationFolder(InputInterface $input, OutputInterface $output)
    {
        /**
         * @param string $folderName
         *
         * @return string
         */
        $validateInstallationFolder = function ($folderName) use ($input) {
            $folderName = rtrim(trim($folderName, ' '), '/');
            // resolve folder-name to current working directory if relative
            if (substr($folderName, 0, 1) == '.') {
                $cwd = OperatingSystem::getCwd();
                $folderName = $cwd . substr($folderName, 1);
            }

            if (empty($folderName)) {
                throw new InvalidArgumentException('Installation folder cannot be empty');
            }

            if (!is_dir($folderName)) {
                if (!@mkdir($folderName, 0777, true)) {
                    throw new InvalidArgumentException('Cannot create folder.');
                }

                return $folderName;
            }

            if ($input->hasOption('noDownload') && $input->getOption('noDownload')) {
                /** @var MagentoHelper $magentoHelper */
                $magentoHelper = new MagentoHelper();
                $magentoHelper->detect($folderName);
                if ($magentoHelper->getRootFolder() !== $folderName) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Folder "%s" is not a Magento working copy (%s)',
                            $folderName,
                            var_export($magentoHelper->getRootFolder(), true)
                        )
                    );
                }

                $localXml = $folderName . '/app/etc/local.xml';
                if (file_exists($localXml)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Magento working copy in %s seems already installed. Please remove %s and retry.',
                            $folderName,
                            $localXml
                        )
                    );
                }
            }

            return $folderName;
        };

        if (($installationFolder = $input->getOption('installationFolder')) == null) {
            $defaultFolder = './magento';
            $question[] = "<question>Enter installation folder:</question> [<comment>" . $defaultFolder . "</comment>]";

            $installationFolder = $this->getHelper('dialog')->askAndValidate(
                $output,
                $question,
                $validateInstallationFolder,
                false,
                $defaultFolder
            );
        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);
        }

        $this->config['installationFolder'] = realpath($installationFolder);
        \chdir($this->config['installationFolder']);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isSourceTypeRepository($type)
    {
        return in_array($type, array('git', 'hg'));
    }

    /**
     * @param string $argument
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $message
     * @return string
     */
    protected function getOrAskForArgument($argument, InputInterface $input, OutputInterface $output, $message = null)
    {
        $inputArgument = $input->getArgument($argument);
        if ($inputArgument === null) {
            $message = $this->getArgumentMessage($argument, $message);

            /** @var  $dialog  \Symfony\Component\Console\Helper\DialogHelper */
            $dialog = $this->getHelper('dialog');
            return $dialog->ask($output, $message);
        }

        return $inputArgument;
    }

    /**
     * @param array $entries zero-indexed array of entries (represented by strings) to select from
     * @param OutputInterface $output
     * @param string $question
     * @return mixed
     */
    protected function askForArrayEntry(array $entries, OutputInterface $output, $question)
    {
        $dialog = '';
        foreach ($entries as $key => $entry) {
            $dialog .= '<comment>[' . ($key + 1) . ']</comment> ' . $entry . "\n";
        }
        $dialog .= "<question>{$question}</question> ";

        $selected = $this->getHelper('dialog')->askAndValidate($output, $dialog, function ($typeInput) use ($entries) {
            if (!in_array($typeInput, range(1, count($entries)))) {
                throw new InvalidArgumentException('Invalid type');
            }

            return $typeInput;
        });

        return $entries[$selected - 1];
    }

    /**
     * @param string $argument
     * @param string $message [optional]
     * @return string
     */
    protected function getArgumentMessage($argument, $message = null)
    {
        if (null === $message) {
            $message = ucfirst($argument);
        }

        return sprintf('<question>%s:</question>', $message);
    }
}
