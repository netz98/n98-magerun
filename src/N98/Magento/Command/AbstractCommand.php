<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Composer\Composer;
use Composer\Downloader\DownloadManager;
use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;
use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Package\PackageInterface;
use InvalidArgumentException;
use Mage;
use N98\Magento\Application;
use N98\Magento\Command\SubCommand\ConfigBag;
use N98\Magento\Command\SubCommand\SubCommandFactory;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\OperatingSystem;
use N98\Util\StringTyped;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class AbstractCommand
 *
 * @package N98\Magento\Command
 */
abstract class AbstractCommand extends AbstractCommandHelper
{
    protected const COMMAND_SECTION_TITLE_TEXT = '';

    public const COMMAND_OPTION_FORMAT = 'format';

    public const COMMAND_OPTION_FORMAT_DEFAULT = null;

    protected const NO_DATA_MESSAGE = 'No data found';

    public const QUESTION_ATTEMPTS = 3;

    /**
     * @var string|null
     */
    protected ?string $_magentoRootFolder = null;

    /**
     * @var array
     */
    protected array $_deprecatedAlias = [];

    /**
     * @var array
     */
    protected array $_websiteCodeMap = [];

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var null|array<<int<0, max>|string, array<string, int|string>>
     */
    protected ?array $data = null;

    protected static bool $detectMagentoFlag = true;

    protected static bool $detectMagentoSilentFlag = true;

    protected static bool $initMagentoFlag = true;

    protected static bool $initMagentoSoftFlag = false;

    protected function configure(): void
    {
        if ($this instanceof CommandDataInterface) {
            $this->addFormatOption();
        }
    }

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

        if (static::$detectMagentoFlag) {
            $this->detectMagento($output, static::$detectMagentoSilentFlag);
        }

        if (static::$initMagentoFlag) {
            $this->initMagento(static::$initMagentoSoftFlag);
        }

        if ($this instanceof CommandDataInterface) {
            $this->setData($input, $output);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (static::COMMAND_SECTION_TITLE_TEXT && $input->getOption(self::COMMAND_OPTION_FORMAT) === null) {
            $this->writeSection($output, static::COMMAND_SECTION_TITLE_TEXT);
        }

        $data = $this->getData();
        if (!count($data)) {
            $output->writeln(sprintf('<comment>%s</comment>', static::NO_DATA_MESSAGE));
            return Command::SUCCESS;
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders($this->getTableHeaders($input, $output))
            ->renderByFormat($output, $data, $input->getOption(self::COMMAND_OPTION_FORMAT));

        return Command::SUCCESS;
    }

    protected function getData(): array
    {
        return $this->data;
    }

    private function _initWebsites()
    {
        $this->_websiteCodeMap = [];
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            $this->_websiteCodeMap[$website->getId()] = $website->getCode();
        }
    }

    /**
     * @param int $websiteId
     * @return string
     */
    protected function _getWebsiteCodeById(int $websiteId): string
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
    protected function _getWebsiteIdByCode(string $websiteCode): int
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
    protected function getCommandConfig(?string $commandClass = null): array
    {
        if (null === $commandClass) {
            $commandClass = get_class($this);
        }

        $application = $this->getApplication();
        return (array) $application->getConfig('commands', $commandClass);
    }

    /**
     * @param OutputInterface $output
     * @param string $text
     * @param string $style
     */
    protected function writeSection(OutputInterface $output, string $text, string $style = 'bg=blue;fg=white')
    {
        $output->writeln(['', $this->getHelper('formatter')->formatBlock($text, $style, true), '']);
    }

    /**
     * Bootstrap magento shop
     *
     * @param bool $soft
     * @return void
     */
    protected function initMagento(bool $soft = false): void
    {
        $application = $this->getApplication();
        $init = $application->initMagento($soft);
        if (!$init) {
            throw new RuntimeException('Application could not be loaded');
        }

        $this->_magentoRootFolder = $application->getMagentoRootFolder();
    }

    /**
     * Search for magento root folder
     *
     * @param OutputInterface $output
     * @param bool $silent print debug messages
     * @throws RuntimeException
     */
    public function detectMagento(OutputInterface $output, bool $silent = true)
    {
        $this->getApplication()->detectMagento();

        $this->_magentoRootFolder = $this->getApplication()->getMagentoRootFolder();

        if (!$silent) {
            $output->writeln(
                '<info>Found Magento in folder "' . $this->_magentoRootFolder . '"</info>'
            );
        }

        if (!empty($this->_magentoRootFolder)) {
            return;
        }

        throw new RuntimeException('Magento folder could not be detected');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return DownloadManager
     */
    protected function getComposerDownloadManager($input, $output)
    {
        return $this->getComposer($input, $output)->getDownloadManager();
    }

    /**
     * @param array|PackageInterface $config
     * @return CompletePackage
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
     * @return CompletePackage
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

        $helper = new MagentoHelper();
        $helper->detect($targetFolder);
        if ($this->isSourceTypeRepository($package->getSourceType()) && $helper->getRootFolder() == $targetFolder) {
            $package->setInstallationSource('source');
            $this->checkRepository($package, $targetFolder);
            $dm->update($package, $package, $targetFolder);
        } else {
            // @todo check cmuench
            $dm->setPreferSource($preferSource);
            $dm->download($package, $targetFolder);
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
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
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
     * @return \Composer
     */
    protected function getComposer(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $config = ['config' => ['secure-http' => false]];

        return \ComposerFactory::create($io, $config);
    }

    /**
     * @param string $alias
     * @param string $message
     * @return AbstractCommand
     */
    protected function addDeprecatedAlias(string $alias, string $message)
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
     * @param string $value
     * @return bool
     */
    protected function _parseBoolOption(string $value): bool
    {
        return StringTyped::parseBoolOption($value);
    }

    /**
     * @param string $value
     * @return bool
     */
    public function parseBoolOption(string $value): bool
    {
        return $this->_parseBoolOption($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function formatActive(string $value): string
    {
        return StringTyped::formatActive($value);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
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
         * @return string
         */
        $validateInstallationFolder = function (string $folderName) use ($input) {
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

            $dialog = $this->getQuestionHelper();
            $questionObj = new Question(
                '<question>Enter installation folder:</question> [<comment>' . $defaultFolder . '</comment>]',
                $defaultFolder
            );
            $questionObj->setValidator($validateInstallationFolder);

            $installationFolder = $dialog->ask($input, $output, $questionObj);
        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);
        }

        $this->config['installationFolder'] = realpath($installationFolder);
        \chdir($this->config['installationFolder']);
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isSourceTypeRepository(string $type): bool
    {
        return in_array($type, ['git', 'hg']);
    }

    /**
     * @param string $argument
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string|null $message
     * @return string
     */
    protected function getOrAskForArgument(
        string          $argument,
        InputInterface  $input,
        OutputInterface $output,
        ?string         $message = null
    ): string {
        $inputArgument = $input->getArgument($argument);
        if ($inputArgument === null) {
            $dialog = $this->getQuestionHelper();
            $message = $this->getArgumentMessage($argument, $message);

            $validation = function (string $answer) use ($argument): string {
                if (trim($answer) === '') {
                    $definition = $this->getNativeDefinition()->getArgument($argument)->getDescription();
                    throw new InvalidArgumentException(
                        $definition . ' cannot be empty.'
                    );
                }

                return $answer;
            };

            $question = new Question($message);
            $question->setNormalizer(function (?string $value): string {
                // $value can be null here
                return $value ? trim($value) : '';
            });
            $question->setValidator($validation);
            $question->setMaxAttempts(self::QUESTION_ATTEMPTS);

            return $dialog->ask($input, $output, $question);
        }

        return $inputArgument;
    }

    /**
     * @param array $entries zero-indexed array of entries (represented by strings) to select from
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @return mixed
     */
    protected function askForArrayEntry(
        array $entries,
        InputInterface $input,
        OutputInterface $output,
        string $question
    ) {
        $validator = function ($typeInput) use ($entries) {
            if (!in_array($typeInput, range(0, count($entries)))) {
                throw new InvalidArgumentException('Invalid type');
            }

            return $typeInput;
        };

        $dialog = $this->getQuestionHelper();
        $question = new ChoiceQuestion(
            "<question>{$question}</question>",
            $entries
        );
        $question->setValidator($validator);

        $selected = $dialog->ask($input, $output, $question);

        return $entries[$selected];
    }

    /**
     * @param string $argument
     * @param string|null $message [optional]
     * @return string
     */
    protected function getArgumentMessage(string $argument, string $message = null)
    {
        if (null === $message) {
            $message = ucfirst($argument);
        }

        return sprintf('<question>%s:</question> ', $message);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $baseNamespace If this is set we can use relative class names.
     *
     * @return SubCommandFactory
     */
    protected function createSubCommandFactory(
        InputInterface $input,
        OutputInterface $output,
        string $baseNamespace = ''
    ) {
        $configBag = new ConfigBag();

        $commandConfig = $this->getCommandConfig();
        if (empty($commandConfig)) {
            $commandConfig = [];
        }

        return new SubCommandFactory(
            $this,
            $baseNamespace,
            $input,
            $output,
            $commandConfig,
            $configBag
        );
    }

    /**
     * @return $this
     */
    public function addFormatOption(): AbstractCommand
    {
        $this->addOption(
            self::COMMAND_OPTION_FORMAT,
            null,
            InputOption::VALUE_OPTIONAL,
            'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']',
            static::COMMAND_OPTION_FORMAT_DEFAULT
        );
        return $this;
    }

    /**
     * @return array<int, string>
     */
    protected function getTableHeaders(InputInterface $input, OutputInterface $output): array
    {
        $data = $this->getData();
        return array_keys(reset($data));
    }

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        $application = parent::getApplication();
        if ($application instanceof Application) {
            return $application;
        }

        throw new RuntimeException('Application not loaded');
    }
}
