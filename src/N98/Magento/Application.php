<?php

declare(strict_types=1);

namespace N98\Magento;

use Composer\Autoload\ClassLoader;
use Exception;
use Mage;
use Mage_Core_Model_Config_Options;
use N98\Magento\Application\Config;
use N98\Magento\Application\ConfigurationLoader;
use N98\Magento\Application\Console\Event;
use N98\Magento\Application\Console\Events;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\Console\Helper\TwigHelper;
use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use UnexpectedValueException;

/**
 * Class Application
 *
 * @package N98\Magento
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    public const APP_NAME = '@application_name@';

    /**
     * @var string
     */
    public const APP_VERSION = '3.0.0-dev';

    private static string $logo = "
     ___ ___
 _ _/ _ ( _ )___ _ __  __ _ __ _ ___ _ _ _  _ _ _
| ' \\_, / _ \\___| '  \\/ _` / _` / -_) '_| || | ' \\
|_||_/_/\\___/   |_|_|_\\__,_\\__, \\___|_|  \\_,_|_||_|
                           |___/
";

    /**
     * Shadow copy of the Application parent when using this concrete setAutoExit() implementation
     *
     * @see BaseApplication
     */
    private bool $autoExitShadow = true;

    /**
     * @var ClassLoader|null
     */
    protected $autoloader;

    protected ?Config $config = null;

    /**
     * @see Application::setConfigurationLoader
     */
    private ?ConfigurationLoader $configurationLoader = null;

    protected ?string $_magentoRootFolder = null;

    protected bool $_magentoEnterprise = false;

    protected int $_magentoMajorVersion = 1;

    protected bool $_isPharMode = false;

    protected bool $_magerunStopFileFound = false;

    protected ?string $_magerunStopFileFolder = null;

    protected bool $_magerunUseDeveloperMode;

    protected bool $_isInitialized = false;

    protected EventDispatcher $dispatcher;

    /**
     * If root dir is set by root-dir option this flag is true
     */
    protected bool $_directRootDir = false;

    protected bool $_magentoDetected = false;

    public function __construct(?ClassLoader $classLoader = null)
    {
        $this->autoloader = $classLoader;

        $appName = self::APP_NAME;

        if (strpos($appName, 'application_name') !== false) {
            $appName = 'n98-magerun';
        }

        parent::__construct($appName, self::APP_VERSION);
    }

    /**
     * @return bool previous auto-exit state
     */
    public function setAutoExit(bool $boolean): bool
    {
        $previous = $this->autoExitShadow;
        $this->autoExitShadow = $boolean;
        parent::setAutoExit($boolean);

        return $previous;
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        /**
         * Root dir
         */
        $rootDirOption = new InputOption(
            '--root-dir',
            '',
            InputOption::VALUE_OPTIONAL,
            'Force magento root dir. No auto detection',
        );
        $inputDefinition->addOption($rootDirOption);

        /**
         * Skip config
         */
        $skipExternalConfig = new InputOption(
            '--skip-config',
            '',
            InputOption::VALUE_NONE,
            'Do not load any custom config.',
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Skip root check
         */
        $skipExternalConfig = new InputOption(
            '--skip-root-check',
            '',
            InputOption::VALUE_NONE,
            'Do not check if n98-magerun runs as root',
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Developer Mode
         */
        $rootDirOption = new InputOption(
            '--developer-mode',
            '',
            InputOption::VALUE_NONE,
            'Instantiate Magento in Developer Mode',
        );
        $inputDefinition->addOption($rootDirOption);

        return $inputDefinition;
    }

    /**
     * Search for magento root folder
     */
    public function detectMagento(?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        // do not detect magento twice
        if ($this->_magentoDetected) {
            return;
        }

        if (!$input instanceof InputInterface) {
            $input = new ArgvInput();
        }

        if (!$output instanceof OutputInterface) {
            $output = new ConsoleOutput();
        }

        if ($this->getMagentoRootFolder() === null) {
            $this->_checkRootDirOption($input);
            $folder = OperatingSystem::getCwd();
        } else {
            $folder = $this->getMagentoRootFolder();
        }

        $folder = $folder ?: '';

        $this->getHelperSet()->set(new MagentoHelper($input, $output), 'magento');
        /** @var MagentoHelper $magentoHelper */
        $magentoHelper = $this->getHelperSet()->get('magento');
        $subFolders = $this->_directRootDir ? [] : $this->config->getDetectSubFolders();

        $this->_magentoDetected = $magentoHelper->detect($folder, $subFolders);
        $this->_magentoRootFolder = $magentoHelper->getRootFolder();
        $this->_magentoEnterprise = $magentoHelper->isEnterpriseEdition();
        $this->_magentoMajorVersion = $magentoHelper->getMajorVersion();
        $this->_magerunStopFileFound = $magentoHelper->isMagerunStopFileFound();
        $this->_magerunStopFileFolder = $magentoHelper->getMagerunStopFileFolder();
        $this->_magerunUseDeveloperMode = ($input->getParameterOption('--developer-mode'));
    }

    /**
     * Add own helpers to helper-set.
     */
    protected function registerHelpers(): void
    {
        $helperSet = $this->getHelperSet();
        $config = $this->config->getConfig();

        foreach ($config['helpers'] as $helperName => $helperClass) {
            if (!class_exists($helperClass)) {
                throw new RuntimeException(
                    sprintf('Nonexistent helper class: "%s", check helpers configuration', $helperClass),
                );
            }

            // Twig helper needs the config-file
            /** @var HelperInterface $helper */
            $helper = TwigHelper::class === $helperClass
                ? new $helperClass($this->config)
                : new $helperClass()
            ;
            $helperSet->set($helper, $helperName);
        }
    }

    /**
     * @return ArgvInput|InputInterface
     */
    protected function checkConfigCommandAlias(InputInterface $input)
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->checkConfigCommandAlias()', E_USER_DEPRECATED);
        return $this->config->checkConfigCommandAlias($input);
    }

    protected function registerConfigCommandAlias(Command $command): void
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerConfigCommandAlias() instead', E_USER_DEPRECATED);
        $this->config->registerConfigCommandAlias($command);
    }

    /**
     * Adds autoloader prefixes from user's config
     */
    protected function registerCustomAutoloaders(): void
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerCustomAutoloaders() instead', E_USER_DEPRECATED);

        $this->config->registerCustomAutoloaders($this->autoloader);
    }

    protected function hasCustomCommands(): bool
    {
        trigger_error(__METHOD__ . ' moved, use config directly instead', E_USER_DEPRECATED);

        return [] !== $this->config->getConfig(['commands', 'customCommands']);
    }

    protected function registerCustomCommands(): void
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerCustomCommands() instead', E_USER_DEPRECATED);

        $this->config->registerCustomCommands($this);
    }

    protected function isCommandDisabled(string $class): bool
    {
        trigger_error(__METHOD__ . ' moved, use config directly instead', E_USER_DEPRECATED);

        $config = $this->config->getConfig();

        return in_array($class, $config['commands']['disabled']);
    }

    /**
     * Override standard command registration. We want alias support.
     */
    public function add(Command $command): Command
    {
        if ($this->config instanceof Config) {
            $this->config->registerConfigCommandAlias($command);
        }

        return parent::add($command);
    }

    public function setPharMode(bool $mode): void
    {
        $this->_isPharMode = $mode;
    }

    public function isPharMode(): bool
    {
        return $this->_isPharMode;
    }

    /**
     * @TODO Move logic into "EventSubscriber"
     */
    public function checkVarDir(OutputInterface $output): ?bool
    {
        $tempVarDir = sys_get_temp_dir() . '/magento/var';
        if (OutputInterface::VERBOSITY_NORMAL > $output->getVerbosity() && !is_dir($tempVarDir)) {
            return null;
        }

        $this->detectMagento(null, $output);
        /* If magento is not installed yet, don't check */
        if (!file_exists($this->_magentoRootFolder . '/app/etc/local.xml')) {
            return null;
        }

        try {
            $this->initMagento();
        } catch (Exception $exception) {
            $message = 'Cannot initialize Magento. Please check your configuration. '
                . 'Some n98-magerun command will not work. Got message: ';
            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                $message .= $exception->getTraceAsString();
            } else {
                $message .= $exception->getMessage();
            }

            $output->writeln($message);

            return null;
        }

        $mageCoreModelConfigOptions = new Mage_Core_Model_Config_Options();
        $currentVarDir = $mageCoreModelConfigOptions->getVarDir();

        if ($currentVarDir == $tempVarDir) {
            $output->writeln([sprintf('<warning>Fallback folder %s is used in n98-magerun</warning>', $tempVarDir), '', 'n98-magerun is using the fallback folder. If there is another folder configured for Magento, this ' .
            'can cause serious problems.', 'Please refer to https://github.com/netz98/n98-magerun/wiki/File-system-permissions ' .
            'for more information.', '']);
        } else {
            $output->writeln([sprintf('<warning>Folder %s found, but not used in n98-magerun</warning>', $tempVarDir), '', 'This might cause serious problems. n98-magerun is using the configured var-folder ' .
            sprintf('<comment>%s</comment>', $currentVarDir), 'Please refer to https://github.com/netz98/n98-magerun/wiki/File-system-permissions ' .
            'for more information.', '']);

            return false;
        }

        return null;
    }

    /**
     * Loads and initializes the Magento application
     *
     * @return bool false if magento root folder is not set, true otherwise
     */
    public function initMagento(bool $soft = false): bool
    {
        if ($this->getMagentoRootFolder() === null) {
            return false;
        }

        $this->_initMagento1($soft);
        return true;
    }

    public function getHelp(): string
    {
        return self::$logo . parent::getHelp();
    }

    public function getLongVersion(): string
    {
        return parent::getLongVersion() . ' by <info>valantic CEC</info>';
    }

    public function isMagentoEnterprise(): bool
    {
        return $this->_magentoEnterprise;
    }

    public function getMagentoRootFolder(): ?string
    {
        return $this->_magentoRootFolder;
    }

    public function setMagentoRootFolder(string $magentoRootFolder): void
    {
        $this->_magentoRootFolder = $magentoRootFolder;
    }

    public function getMagentoMajorVersion(): int
    {
        return $this->_magentoMajorVersion;
    }

    public function getAutoloader(): ?ClassLoader
    {
        return $this->autoloader;
    }

    public function setAutoloader(ClassLoader $classLoader): void
    {
        $this->autoloader = $classLoader;
    }

    /**
     * Get config array
     *
     * Specify one key per parameter to traverse the config. Then returns null
     * if the path of the key(s) can not be obtained.
     *
     * @param string|int $key ... (optional)
     */
    public function getConfig($key = null): ?array
    {
        $array = $this->config->getConfig();

        $keys = func_get_args();
        foreach ($keys as $key) {
            if (null === $key) {
                continue;
            }

            if (!isset($array[$key])) {
                return null;
            }

            $array = $array[$key];
        }

        return $array;
    }

    public function setConfig(array $config): void
    {
        $this->config->setConfig($config);
    }

    public function isMagerunStopFileFound(): bool
    {
        return $this->_magerunStopFileFound;
    }

    /**
     * Runs the current application with possible command aliases
     *
     * @param InputInterface $input An Input instance
     * @param OutputInterface $output An Output instance
     * @throws Throwable
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $event = new Event($this, $input, $output);
        $this->dispatcher->dispatch($event, Events::RUN_BEFORE);

        /**
         * only for compatibility to old versions.
         */
        $event = new ConsoleEvent(new Command('dummy'), $input, $output);
        $this->dispatcher->dispatch($event, 'console.run.before');

        $input = $this->config->checkConfigCommandAlias($input);
        if ($output instanceof ConsoleOutput) {
            $this->checkVarDir($output->getErrorOutput());
        }

        return parent::doRun($input, $output);
    }

    /**
     * @throws Exception
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        if (!$input instanceof InputInterface) {
            $input = new ArgvInput();
        }

        if (!$output instanceof OutputInterface) {
            $output = new ConsoleOutput();
        }

        $this->_addOutputStyles($output);
        if ($output instanceof ConsoleOutput) {
            $this->_addOutputStyles($output->getErrorOutput());
        }

        $this->configureIO($input, $output);

        try {
            $this->init([], $input, $output);
        } catch (Exception $exception) {
            $output = new ConsoleOutput();
            $this->renderThrowable($exception, $output->getErrorOutput());
        }

        $return = parent::run($input, $output);

        // Fix for no return values -> used in interactive shell to prevent error output
        if ($return === null) {
            return 0;
        }

        return $return;
    }

    public function init(array $initConfig = [], ?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        if ($this->_isInitialized) {
            return;
        }

        // Suppress DateTime warnings
        date_default_timezone_set(@date_default_timezone_get());

        // Initialize EventDispatcher early
        $this->dispatcher = new EventDispatcher();
        $this->setDispatcher($this->dispatcher);

        $input = $input instanceof InputInterface ? $input : new ArgvInput();
        $output = $output instanceof OutputInterface ? $output : new ConsoleOutput();

        if ($this->config instanceof Config) {
            throw new UnexpectedValueException('Config already initialized');
        }

        $loadExternalConfig = !$input->hasParameterOption('--skip-config');
        $this->config = new Config($initConfig, $this->isPharMode(), $output);
        $config = $this->config;
        if ($this->configurationLoader instanceof ConfigurationLoader) {
            $config->setLoader($this->configurationLoader);
        }

        $config->loadPartialConfig($loadExternalConfig);
        $this->detectMagento($input, $output);
        $configurationLoader = $config->getLoader();
        $configurationLoader->loadStageTwo($this->_magentoRootFolder, $loadExternalConfig, $this->_magerunStopFileFolder);

        $config->load();

        if ($autoloader = $this->autoloader) {
            $config->registerCustomAutoloaders($autoloader);
            $this->registerEventSubscribers();
            $config->registerCustomCommands($this);
        }

        $this->registerHelpers();

        $this->_isInitialized = true;
    }

    public function reinit(array $initConfig = [], ?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        $this->_isInitialized       = false;
        $this->_magentoDetected     = false;
        $this->_magentoRootFolder   = '';
        $this->config               = null;
        $this->init($initConfig, $input, $output);
    }

    protected function registerEventSubscribers(): void
    {
        $config = $this->config->getConfig();
        $subscriberClasses = $config['event']['subscriber'];
        foreach ($subscriberClasses as $subscriberClass) {
            /** @var EventSubscriberInterface $subscriber */
            $subscriber = new $subscriberClass();
            $this->dispatcher->addSubscriber($subscriber);
        }
    }

    /**
     * @deprecated 1.97.27
     */
    protected function _checkSkipConfigOption(InputInterface $input): bool
    {
        trigger_error(
            __METHOD__ . ' removed, use $input->hasParameterOption(\'--skip-config\') instead',
            E_USER_DEPRECATED,
        );

        return $input->hasParameterOption('--skip-config');
    }

    protected function _checkRootDirOption(InputInterface $input): void
    {
        $rootDir = $input->getParameterOption('--root-dir');
        if (is_string($rootDir)) {
            $this->setRootDir($rootDir);
        }
    }

    /**
     * Set root dir (chdir()) of magento directory
     *
     * @param string $path to Magento directory
     */
    private function setRootDir(string $path): void
    {
        if (isset($path[0]) && '~' === $path[0]) {
            $path = OperatingSystem::getHomeDir() . substr($path, 1);
        }

        $this->_directRootDir = true;
        $folder = realpath($path);
        if ($folder && is_dir($folder)) {
            chdir($folder);
        }
    }

    protected function _initMagento1(bool $soft = false): void
    {
        // Load Mage class definition
        Initializer::bootstrap($this->_magentoRootFolder);

        // skip Mage::app init routine and return
        if ($soft) {
            return;
        }

        $initSettings = $this->config->getConfig('init');

        Mage::app($initSettings['code'], $initSettings['type'], $initSettings['options']);
        if ($this->_magerunUseDeveloperMode) {
            Mage::setIsDeveloperMode(true);
        }
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    public function getConfigurationLoader(array $initConfig, OutputInterface $output): ConfigurationLoader
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->getLoader()', E_USER_DEPRECATED);

        unset($initConfig, $output);

        $loader = $this->config instanceof Config ? $this->config->getLoader() : $this->configurationLoader;

        if (!$loader instanceof ConfigurationLoader) {
            throw new RuntimeException('ConfigurationLoader is not yet available, initialize it or Config first');
        }

        return $loader;
    }

    /**
     * @return $this
     */
    public function setConfigurationLoader(ConfigurationLoader $configurationLoader)
    {
        if ($this->config instanceof Config) {
            $this->config->setLoader($configurationLoader);
        } else {
            /* inject loader to be used later when config is created in */
            /* @see Application::init */
            $this->configurationLoader = $configurationLoader;
        }

        return $this;
    }

    protected function _addOutputStyles(OutputInterface $output): void
    {
        $output->getFormatter()->setStyle('debug', new OutputFormatterStyle('magenta', 'white'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('red', 'yellow', ['bold']));
    }
}
