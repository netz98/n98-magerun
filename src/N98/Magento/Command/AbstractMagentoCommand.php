<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Composer\Composer;
use Composer\Downloader\DownloadManager;
use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;
use Composer\Package\CompleteAliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use Mage;
use Mage_Core_Helper_Abstract;
use Mage_Core_Helper_Data;
use Mage_Core_Model_Abstract;
use Mage_Core_Model_Resource_Db_Collection_Abstract;
use N98\Magento\Application;
use N98\Magento\Command\SubCommand\ConfigBag;
use N98\Magento\Command\SubCommand\SubCommandFactory;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\Console\Helper\IoHelper;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\Console\Helper\ParameterHelper;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use N98\Util\OperatingSystem;
use N98\Util\StringTyped;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

use function chdir;

/**
 * Class AbstractMagentoCommand
 *
 * @package N98\Magento\Command
 *
 * @method Application getApplication()
 */
abstract class AbstractMagentoCommand extends Command
{
    protected ?string $_magentoRootFolder;

    protected int $_magentoMajorVersion = 1;

    protected bool $_magentoEnterprise = false;

    protected array $_deprecatedAlias = [];

    protected array $_websiteCodeMap = [];

    protected array $config;

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->checkDeprecatedAliases($input, $output);
    }

    private function _initWebsites(): void
    {
        $this->_websiteCodeMap = [];
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            $this->_websiteCodeMap[$website->getId()] = $website->getCode();
        }
    }

    protected function _getWebsiteCodeById(int $websiteId): string
    {
        if ($this->_websiteCodeMap === []) {
            $this->_initWebsites();
        }

        if (isset($this->_websiteCodeMap[$websiteId])) {
            return $this->_websiteCodeMap[$websiteId];
        }

        return '';
    }

    protected function _getWebsiteIdByCode(string $websiteCode): int
    {
        if ($this->_websiteCodeMap === []) {
            $this->_initWebsites();
        }

        $websiteMap = array_flip($this->_websiteCodeMap);

        return $websiteMap[$websiteCode];
    }

    protected function getCommandConfig(?string $commandClass = null): array
    {
        if (null === $commandClass) {
            $commandClass = get_class($this);
        }

        $application = $this->getApplication();
        return (array) $application->getConfig('commands', $commandClass);
    }

    protected function writeSection(OutputInterface $output, string $text, string $style = 'bg=blue;fg=white'): void
    {
        /** @var FormatterHelper $helper */
        $helper = $this->getHelper('formatter');
        $output->writeln(['', $helper->formatBlock($text, $style, true), '']);
    }

    /**
     * Bootstrap magento shop
     */
    protected function initMagento(bool $soft = false): bool
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
     * @param bool $silent print debug messages
     * @throws RuntimeException
     */
    public function detectMagento(OutputInterface $output, bool $silent = true): void
    {
        $this->getApplication()->detectMagento();

        $this->_magentoEnterprise = $this->getApplication()->isMagentoEnterprise();
        $this->_magentoRootFolder = $this->getApplication()->getMagentoRootFolder();
        $this->_magentoMajorVersion = $this->getApplication()->getMagentoMajorVersion();

        if (!$silent) {
            $editionString = ($this->_magentoEnterprise ? ' (Enterprise Edition) ' : '');
            $output->writeln(
                '<info>Found Magento ' . $editionString . 'in folder "' . $this->_magentoRootFolder . '"</info>',
            );
        }

        if (isset($this->_magentoRootFolder) && ($this->_magentoRootFolder !== '' && $this->_magentoRootFolder !== '0')) {
            return;
        }

        throw new RuntimeException('Magento folder could not be detected');
    }

    /**
     * Die if not Enterprise
     */
    protected function requireEnterprise(OutputInterface $output): void
    {
        if (!$this->_magentoEnterprise) {
            $output->writeln('<error>Enterprise Edition is required but was not detected</error>');
            exit;
        }
    }

    protected function getCoreHelper(): Mage_Core_Helper_Data
    {
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        return $helper;
    }

    protected function getComposerDownloadManager(InputInterface $input, OutputInterface $output): DownloadManager
    {
        return $this->getComposer($input, $output)->getDownloadManager();
    }

    /**
     * @param mixed $config
     * @return CompleteAliasPackage|CompletePackage
     */
    protected function createComposerPackageByConfig($config)
    {
        $arrayLoader = new PackageLoader();
        return $arrayLoader->load($config);
    }

    /**
     * @param array|PackageInterface $config
     * @return CompletePackage|PackageInterface
     */
    protected function downloadByComposerConfig(
        InputInterface  $input,
        OutputInterface $output,
        $config,
        string          $targetFolder,
        bool            $preferSource = true
    ) {
        $downloadManager = $this->getComposerDownloadManager($input, $output);
        if (!$config instanceof PackageInterface) {
            $package = $this->createComposerPackageByConfig($config);
        } else {
            $package = $config;
        }

        $magentoHelper = new MagentoHelper();
        $magentoHelper->detect($targetFolder);
        if ($this->isSourceTypeRepository($package->getSourceType()) && $magentoHelper->getRootFolder() === $targetFolder) {
            $package->setInstallationSource('source');
            $this->checkRepository($package, $targetFolder);
            $downloadManager->update($package, $package, $targetFolder);
        } else {
            // @todo check cmuench
            $downloadManager->setPreferSource($preferSource);
            $downloadManager->download($package, $targetFolder);
        }

        return $package;
    }

    /**
     * brings locally cached repository up to date if it is missing the requested tag
     */
    protected function checkRepository(PackageInterface $package, string $targetFolder): void
    {
        if ($package->getSourceType() == 'git') {
            $command = sprintf(
                'cd %s && git rev-parse refs/tags/%s',
                escapeshellarg($this->normalizePath($targetFolder)),
                escapeshellarg($package->getSourceReference()),
            );
            $existingTags = shell_exec($command);
            if ($existingTags === '' || $existingTags === '0' || $existingTags === false || $existingTags === null) {
                $command = sprintf('cd %s && git fetch', escapeshellarg($this->normalizePath($targetFolder)));
                shell_exec($command);
            }
        } elseif ($package->getSourceType() == 'hg') {
            $command = sprintf(
                'cd %s && hg log --template "{tags}" -r %s',
                escapeshellarg($targetFolder),
                escapeshellarg($package->getSourceReference()),
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
     */
    protected function normalizePath(string $path): string
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return strtr($path, '/', '\\');
        }

        return $path;
    }

    /**
     * Obtain composer
     */
    protected function getComposer(InputInterface $input, OutputInterface $output): Composer
    {
        $consoleIO = new ConsoleIO($input, $output, $this->getHelperSet());
        $config = ['config' => ['secure-http' => false]];

        return ComposerFactory::create($consoleIO, $config);
    }

    /**
     * @return $this
     */
    protected function addDeprecatedAlias(string $alias, string $message)
    {
        $this->_deprecatedAlias[$alias] = $message;

        return $this;
    }

    protected function checkDeprecatedAliases(InputInterface $input, OutputInterface $output): void
    {
        if (isset($this->_deprecatedAlias[$input->getArgument('command')])) {
            $output->writeln(
                '<error>Deprecated:</error> <comment>' . $this->_deprecatedAlias[$input->getArgument('command')] .
                '</comment>',
            );
        }
    }

    protected function _getModel(string $class): Mage_Core_Model_Abstract
    {
        /** @var Mage_Core_Model_Abstract $model */
        $model = Mage::getModel($class);
        return $model;
    }

    protected function _getHelper(string $class): Mage_Core_Helper_Abstract
    {
        return Mage::helper($class);
    }

    protected function _getSingleton(string $class): Mage_Core_Model_Abstract
    {
        /** @var Mage_Core_Model_Abstract $model */
        $model = Mage::getSingleton($class);
        return $model;
    }

    protected function _getResourceModel(string $class): Mage_Core_Model_Resource_Db_Collection_Abstract
    {
        /** @var Mage_Core_Model_Resource_Db_Collection_Abstract $model */
        $model = Mage::getResourceModel($class);
        return $model;
    }

    protected function _getResourceSingleton(string $class): Mage_Core_Model_Resource_Db_Collection_Abstract
    {
        /** @var Mage_Core_Model_Resource_Db_Collection_Abstract $model */
        $model = Mage::getResourceSingleton($class);
        return $model;
    }

    protected function _parseBoolOption(string $value): bool
    {
        return StringTyped::parseBoolOption($value);
    }

    public function parseBoolOption(string $value): bool
    {
        return $this->_parseBoolOption($value);
    }

    protected function formatActive(string $value): string
    {
        return StringTyped::formatActive($value);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->getHelperSet()->setCommand($this);

        return parent::run($input, $output);
    }

    protected function chooseInstallationFolder(InputInterface $input, OutputInterface $output): void
    {
        /**
         * @param string $folderName
         * @return string
         */
        $validateInstallationFolder = function (string $folderName) use ($input) {
            $folderName = rtrim(trim($folderName, ' '), '/');
            // resolve folder-name to current working directory if relative
            if (substr($folderName, 0, 1) === '.') {
                $cwd = OperatingSystem::getCwd();
                $folderName = $cwd . substr($folderName, 1);
            }

            if ($folderName === '' || $folderName === '0') {
                throw new InvalidArgumentException('Installation folder cannot be empty');
            }

            if (!is_dir($folderName)) {
                if (!@mkdir($folderName, 0777, true)) {
                    throw new InvalidArgumentException('Cannot create folder.');
                }

                return $folderName;
            }

            if ($input->hasOption('noDownload') && $input->getOption('noDownload')) {
                $magentoHelper = new MagentoHelper();
                $magentoHelper->detect($folderName);
                if ($magentoHelper->getRootFolder() !== $folderName) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Folder "%s" is not a Magento working copy (%s)',
                            $folderName,
                            var_export($magentoHelper->getRootFolder(), true),
                        ),
                    );
                }

                $localXml = $folderName . '/app/etc/local.xml';
                if (file_exists($localXml)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Magento working copy in %s seems already installed. Please remove %s and retry.',
                            $folderName,
                            $localXml,
                        ),
                    );
                }
            }

            return $folderName;
        };

        if (($installationFolder = $input->getOption('installationFolder')) == null) {
            $defaultFolder = './magento';

            $dialog = $this->getQuestionHelper();
            $question = new Question(
                '<question>Enter installation folder:</question> [<comment>' . $defaultFolder . '</comment>]',
                $defaultFolder,
            );
            $question->setValidator($validateInstallationFolder);

            $installationFolder = $dialog->ask($input, $output, $question);
        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);
        }

        $this->config['installationFolder'] = (string) realpath($installationFolder);
        chdir($this->config['installationFolder']);
    }

    protected function isSourceTypeRepository(string $type): bool
    {
        return in_array($type, ['git', 'hg']);
    }

    protected function getOrAskForArgument(string $argument, InputInterface $input, OutputInterface $output, ?string $message = null): ?string
    {
        $inputArgument = $input->getArgument($argument);
        if ($inputArgument === null) {
            $message = $this->getArgumentMessage($argument, $message);

            $dialog = $this->getQuestionHelper();
            return $dialog->ask($input, $output, new Question($message));
        }

        return $inputArgument;
    }

    /**
     * @param array $entries zero-indexed array of entries (represented by strings) to select from
     * @return mixed
     */
    protected function askForArrayEntry(array $entries, InputInterface $input, OutputInterface $output, string $question)
    {
        $validator = function ($typeInput) use ($entries) {
            if (!in_array($typeInput, range(0, count($entries)))) {
                throw new InvalidArgumentException('Invalid type');
            }

            return $typeInput;
        };

        $questionHelper = $this->getQuestionHelper();
        $question = new ChoiceQuestion(
            sprintf('<question>%s</question>', $question),
            $entries,
        );
        $question->setValidator($validator);

        $selected = $questionHelper->ask($input, $output, $question);

        return $entries[$selected];
    }

    protected function getArgumentMessage(string $argument, ?string $message = null): string
    {
        if (is_null($message)) {
            $message = ucfirst($argument);
        }

        return sprintf('<question>%s:</question> ', $message);
    }

    /**
     * @param string $baseNamespace If this is set we can use relative class names.
     */
    protected function createSubCommandFactory(
        InputInterface $input,
        OutputInterface $output,
        string $baseNamespace = ''
    ): SubCommandFactory {
        $configBag      = new ConfigBag();
        $commandConfig  = $this->getCommandConfig();

        return new SubCommandFactory(
            $this,
            $baseNamespace,
            $input,
            $output,
            $commandConfig,
            $configBag,
        );
    }

    /**
     * Adds console command "format" option
     *
     * Output result as csv, json, xml or text
     *
     * @return $this
     */
    public function addFormatOption(): self
    {
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_OPTIONAL,
            'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']',
        );
        return $this;
    }

    public function getDatabaseHelper(): DatabaseHelper
    {
        /** @var DatabaseHelper $helper */
        $helper = $this->getHelper('database');
        return $helper;
    }

    public function getIoHelper(): IoHelper
    {
        /** @var IoHelper $helper */
        $helper = $this->getHelper('io');
        return $helper;
    }

    public function getParameterHelper(): ParameterHelper
    {
        /** @var ParameterHelper $helper */
        $helper = $this->getHelper('parameter');
        return $helper;
    }

    public function getQuestionHelper(): QuestionHelper
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper;
    }

    public function getTableHelper(): TableHelper
    {
        /** @var TableHelper $helper */
        $helper = $this->getHelper('table');
        return $helper;
    }
}
