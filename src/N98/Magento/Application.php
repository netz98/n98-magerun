<?php

namespace N98\Magento;

use Composer\Autoload\ClassLoader;
use Exception;
use Mage;
use Magento\Mtf\EntryPoint\EntryPoint;
use N98\Magento\Application\Config;
use N98\Magento\Application\ConfigurationLoader;
use N98\Magento\Application\Console\Events;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use UnexpectedValueException;

class Application extends BaseApplication
{
    /**
     * @var string
     */
    const APP_NAME = 'n98-magerun';

    /**
     * @var string
     */
    const APP_VERSION = '1.103.3';

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
    private static $logo = "
     ___ ___
 _ _/ _ ( _ )___ _ __  __ _ __ _ ___ _ _ _  _ _ _
| ' \\_, / _ \\___| '  \\/ _` / _` / -_) '_| || | ' \\
|_||_/_/\\___/   |_|_|_\\__,_\\__, \\___|_|  \\_,_|_||_|
                           |___/
";

    /**
     * Shadow copy of the Application parent when using this concrete setAutoExit() implementation
     *
     * @see \Symfony\Component\Console\Application::$autoExit
     * @var bool
     */
    private $autoExitShadow = true;

    /**
     * @var ClassLoader
     */
    protected $autoloader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @see \N98\Magento\Application::setConfigurationLoader()
     * @var ConfigurationLoader
     */
    private $configurationLoaderInjected;

    /**
     * @var string
     */
    protected $_magentoRootFolder = null;

    /**
     * @var bool
     */
    protected $_magentoEnterprise = false;

    /**
     * @var int
     */
    protected $_magentoMajorVersion = self::MAGENTO_MAJOR_VERSION_1;

    /**
     * @var EntryPoint
     */
    protected $_magento2EntryPoint = null;

    /**
     * @var bool
     */
    protected $_isPharMode = false;

    /**
     * @var bool
     */
    protected $_magerunStopFileFound = false;

    /**
     * @var string
     */
    protected $_magerunStopFileFolder = null;

    /**
     * @var null
     */
    protected $_magerunUseDeveloperMode = null;

    /**
     * @var bool
     */
    protected $_isInitialized = false;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * If root dir is set by root-dir option this flag is true
     *
     * @var bool
     */
    protected $_directRootDir = false;

    /**
     * @var bool
     */
    protected $_magentoDetected = false;

    /**
     * @param ClassLoader $autoloader
     */
    public function __construct($autoloader = null)
    {
        $this->autoloader = $autoloader;
        parent::__construct(self::APP_NAME, self::APP_VERSION);
    }

    /**
     * @param bool $boolean
     * @return bool previous auto-exit state
     */
    public function setAutoExit($boolean)
    {
        $previous = $this->autoExitShadow;
        $this->autoExitShadow = $boolean;
        parent::setAutoExit($boolean);

        return $previous;
    }

    /**
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        /**
         * Root dir
         */
        $rootDirOption = new InputOption(
            '--root-dir',
            '',
            InputOption::VALUE_OPTIONAL,
            'Force magento root dir. No auto detection'
        );
        $inputDefinition->addOption($rootDirOption);

        /**
         * Skip config
         */
        $skipExternalConfig = new InputOption(
            '--skip-config',
            '',
            InputOption::VALUE_NONE,
            'Do not load any custom config.'
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Skip root check
         */
        $skipExternalConfig = new InputOption(
            '--skip-root-check',
            '',
            InputOption::VALUE_NONE,
            'Do not check if n98-magerun runs as root'
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Developer Mode
         */
        $rootDirOption = new InputOption(
            '--developer-mode',
            '',
            InputOption::VALUE_NONE,
            'Instantiate Magento in Developer Mode'
        );
        $inputDefinition->addOption($rootDirOption);

        return $inputDefinition;
    }

    /**
     * Search for magento root folder
     *
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     * @return void
     */
    public function detectMagento(InputInterface $input = null, OutputInterface $output = null)
    {
        // do not detect magento twice
        if ($this->_magentoDetected) {
            return;
        }

        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        if ($this->getMagentoRootFolder() === null) {
            $this->_checkRootDirOption($input);
            $folder = OperatingSystem::getCwd();
        } else {
            $folder = $this->getMagentoRootFolder();
        }

        $this->getHelperSet()->set(new MagentoHelper($input, $output), 'magento');
        $magentoHelper = $this->getHelperSet()->get('magento');
        /* @var $magentoHelper MagentoHelper */
        if (!$this->_directRootDir) {
            $subFolders = $this->config->getDetectSubFolders();
        } else {
            $subFolders = array();
        }

        $this->_magentoDetected = $magentoHelper->detect($folder, $subFolders);
        $this->_magentoRootFolder = $magentoHelper->getRootFolder();
        $this->_magentoEnterprise = $magentoHelper->isEnterpriseEdition();
        $this->_magentoMajorVersion = $magentoHelper->getMajorVersion();
        $this->_magerunStopFileFound = $magentoHelper->isMagerunStopFileFound();
        $this->_magerunStopFileFolder = $magentoHelper->getMagerunStopFileFolder();
        $this->_magerunUseDeveloperMode = ($input->getParameterOption('--developer-mode'));
    }

    /**
     * Add own helpers to helperset.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        $helperSet = $this->getHelperSet();
        $config = $this->config->getConfig();

        foreach ($config['helpers'] as $helperName => $helperClass) {
            if (!class_exists($helperClass)) {
                throw new RuntimeException(
                    sprintf('Nonexistent helper class: "%s", check helpers configuration', $helperClass)
                );
            }

            // Twig helper needs the config-file
            $helper = 'N98\Util\Console\Helper\TwigHelper' === $helperClass
                ? new $helperClass($this->config)
                : new $helperClass()
            ;
            $helperSet->set($helper, $helperName);
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return ArgvInput|InputInterface
     */
    protected function checkConfigCommandAlias(InputInterface $input)
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->checkConfigCommandAlias()', E_USER_DEPRECATED);

        return $this->config->checkConfigCommandAlias($input);
    }

    /**
     * @param Command $command
     */
    protected function registerConfigCommandAlias(Command $command)
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerConfigCommandAlias() instead', E_USER_DEPRECATED);

        return $this->config->registerConfigCommandAlias($command);
    }

    /**
     * Adds autoloader prefixes from user's config
     */
    protected function registerCustomAutoloaders()
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerCustomAutoloaders() instead', E_USER_DEPRECATED);

        $this->config->registerCustomAutoloaders($this->autoloader);
    }

    /**
     * @return bool
     */
    protected function hasCustomCommands()
    {
        trigger_error(__METHOD__ . ' moved, use config directly instead', E_USER_DEPRECATED);

        return 0 < count($this->config->getConfig(array('commands', 'customCommands')));
    }

    /**
     * @return void
     */
    protected function registerCustomCommands()
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->registerCustomCommands() instead', E_USER_DEPRECATED);

        $this->config->registerCustomCommands($this);
    }

    /**
     * @param string $class
     * @return bool
     */
    protected function isCommandDisabled($class)
    {
        trigger_error(__METHOD__ . ' moved, use config directly instead', E_USER_DEPRECATED);

        $config = $this->config->getConfig();

        return in_array($class, $config['commands']['disabled']);
    }

    /**
     * Override standard command registration. We want alias support.
     *
     * @param Command $command
     *
     * @return Command
     */
    public function add(Command $command)
    {
        if ($this->config) {
            $this->config->registerConfigCommandAlias($command);
        }

        return parent::add($command);
    }

    /**
     * @param bool $mode
     */
    public function setPharMode($mode)
    {
        $this->_isPharMode = $mode;
    }

    /**
     * @return bool
     */
    public function isPharMode()
    {
        return $this->_isPharMode;
    }

    /**
     * @TODO Move logic into "EventSubscriber"
     *
     * @param OutputInterface $output
     * @return null|false
     */
    public function checkVarDir(OutputInterface $output)
    {
        $tempVarDir = sys_get_temp_dir() . '/magento/var';
        if (!OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity() && !is_dir($tempVarDir)) {
            return;
        }

        $this->detectMagento(null, $output);
        /* If magento is not installed yet, don't check */
        if ($this->_magentoRootFolder === null
            || !file_exists($this->_magentoRootFolder . '/app/etc/local.xml')
        ) {
            return;
        }

        try {
            $this->initMagento();
        } catch (Exception $e) {
            $message = 'Cannot initialize Magento. Please check your configuration. '
                . 'Some n98-magerun command will not work. Got message: ';
            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                $message .= $e->getTraceAsString();
            } else {
                $message .= $e->getMessage();
            }
            $output->writeln($message);

            return;
        }

        $configOptions = new \Mage_Core_Model_Config_Options();
        $currentVarDir = $configOptions->getVarDir();

        if ($currentVarDir == $tempVarDir) {
            $output->writeln(array(
                sprintf('<warning>Fallback folder %s is used in n98-magerun</warning>', $tempVarDir),
                '',
                'n98-magerun is using the fallback folder. If there is another folder configured for Magento, this ' .
                'can cause serious problems.',
                'Please refer to https://github.com/netz98/n98-magerun/wiki/File-system-permissions ' .
                'for more information.',
                '',
            ));
        } else {
            $output->writeln(array(
                sprintf('<warning>Folder %s found, but not used in n98-magerun</warning>', $tempVarDir),
                '',
                "This might cause serious problems. n98-magerun is using the configured var-folder " .
                "<comment>$currentVarDir</comment>",
                'Please refer to https://github.com/netz98/n98-magerun/wiki/File-system-permissions ' .
                'for more information.',
                '',
            ));

            return false;
        }
    }

    /**
     * Loads and initializes the Magento application
     *
     * @param bool $soft
     *
     * @return bool false if magento root folder is not set, true otherwise
     */
    public function initMagento($soft = false)
    {
        if ($this->getMagentoRootFolder() === null) {
            return false;
        }

        $isMagento2 = $this->_magentoMajorVersion === self::MAGENTO_MAJOR_VERSION_2;
        if ($isMagento2) {
            $this->_initMagento2();
        } else {
            $this->_initMagento1($soft);
        }

        return true;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    public function getLongVersion()
    {
        return parent::getLongVersion() . ' by <info>netz98 GmbH</info>';
    }

    /**
     * @return boolean
     */
    public function isMagentoEnterprise()
    {
        return $this->_magentoEnterprise;
    }

    /**
     * @return string
     */
    public function getMagentoRootFolder()
    {
        return $this->_magentoRootFolder;
    }

    /**
     * @param string $magentoRootFolder
     */
    public function setMagentoRootFolder($magentoRootFolder)
    {
        $this->_magentoRootFolder = $magentoRootFolder;
    }

    /**
     * @return int
     */
    public function getMagentoMajorVersion()
    {
        return $this->_magentoMajorVersion;
    }

    /**
     * @return ClassLoader
     */
    public function getAutoloader()
    {
        return $this->autoloader;
    }

    /**
     * @param ClassLoader $autoloader
     */
    public function setAutoloader(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * Get config array
     *
     * Specify one key per parameter to traverse the config. Then returns null
     * if the path of the key(s) can not be obtained.
     *
     * @param string|int $key ... (optional)
     *
     * @return array|null
     */
    public function getConfig($key = null)
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

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config->setConfig($config);
    }

    /**
     * @return boolean
     */
    public function isMagerunStopFileFound()
    {
        return $this->_magerunStopFileFound;
    }

    /**
     * Runs the current application with possible command aliases
     *
     * @param InputInterface $input An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $event = new Application\Console\Event($this, $input, $output);
        $this->dispatcher->dispatch(Events::RUN_BEFORE, $event);

        /**
         * only for compatibility to old versions.
         */
        $event = new ConsoleEvent(new Command('dummy'), $input, $output);
        $this->dispatcher->dispatch('console.run.before', $event);

        $input = $this->config->checkConfigCommandAlias($input);
        if ($output instanceof ConsoleOutput) {
            $this->checkVarDir($output->getErrorOutput());
        }

        return parent::doRun($input, $output);
    }

    /**
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     *
     * @return int
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }
        $this->_addOutputStyles($output);
        if ($output instanceof ConsoleOutput) {
            $this->_addOutputStyles($output->getErrorOutput());
        }

        $this->configureIO($input, $output);

        try {
            $this->init(array(), $input, $output);
        } catch (Exception $e) {
            $output = new ConsoleOutput();
            $this->renderException($e, $output->getErrorOutput());
        }

        $return = parent::run($input, $output);

        // Fix for no return values -> used in interactive shell to prevent error output
        if ($return === null) {
            return 0;
        }

        return $return;
    }

    /**
     * @param array $initConfig [optional]
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     *
     * @return void
     */
    public function init(array $initConfig = array(), InputInterface $input = null, OutputInterface $output = null)
    {
        if ($this->_isInitialized) {
            return;
        }

        // Suppress DateTime warnings
        date_default_timezone_set(@date_default_timezone_get());

        // Initialize EventDispatcher early
        $this->dispatcher = new EventDispatcher();
        $this->setDispatcher($this->dispatcher);

        $input = $input ?: new ArgvInput();
        $output = $output ?: new ConsoleOutput();

        if (null !== $this->config) {
            throw new UnexpectedValueException(sprintf('Config already initialized'));
        }

        $loadExternalConfig = !$input->hasParameterOption('--skip-config');

        $this->config = $config = new Config($initConfig, $this->isPharMode(), $output);
        if ($this->configurationLoaderInjected) {
            $config->setLoader($this->configurationLoaderInjected);
        }
        $config->loadPartialConfig($loadExternalConfig);
        $this->detectMagento($input, $output);
        $configLoader = $config->getLoader();
        $configLoader->loadStageTwo($this->_magentoRootFolder, $loadExternalConfig, $this->_magerunStopFileFolder);
        $config->load();

        if ($autoloader = $this->autoloader) {
            $config->registerCustomAutoloaders($autoloader);
            $this->registerEventSubscribers();
            $config->registerCustomCommands($this);
        }

        $this->registerHelpers();

        $this->_isInitialized = true;
    }

    /**
     * @param array $initConfig [optional]
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     */
    public function reinit($initConfig = array(), InputInterface $input = null, OutputInterface $output = null)
    {
        $this->_isInitialized = false;
        $this->_magentoDetected = false;
        $this->_magentoRootFolder = null;
        $this->config = null;
        $this->init($initConfig, $input, $output);
    }

    /**
     * @return void
     */
    protected function registerEventSubscribers()
    {
        $config = $this->config->getConfig();
        $subscriberClasses = $config['event']['subscriber'];
        foreach ($subscriberClasses as $subscriberClass) {
            $subscriber = new $subscriberClass();
            $this->dispatcher->addSubscriber($subscriber);
        }
    }

    /**
     * @param InputInterface $input
     * @return bool
     * @deprecated 1.97.27
     */
    protected function _checkSkipConfigOption(InputInterface $input)
    {
        trigger_error(
            __METHOD__ . ' removed, use $input->hasParameterOption(\'--skip-config\') instead',
            E_USER_DEPRECATED
        );

        return $input->hasParameterOption('--skip-config');
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function _checkRootDirOption(InputInterface $input)
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
    private function setRootDir($path)
    {
        if (isset($path[0]) && '~' === $path[0]) {
            $path = OperatingSystem::getHomeDir() . substr($path, 1);
        }

        $folder = realpath($path);
        $this->_directRootDir = true;
        if (is_dir($folder)) {
            chdir($folder);
        }
    }

    /**
     * @param bool $soft
     *
     * @return void
     */
    protected function _initMagento1($soft = false)
    {
        // Load Mage class definition
        Initialiser::bootstrap($this->_magentoRootFolder);

        // skip Mage::app init routine and return
        if ($soft === true) {
            return;
        }

        $initSettings = $this->config->getConfig('init');

        Mage::app($initSettings['code'], $initSettings['type'], $initSettings['options']);
        if ($this->_magerunUseDeveloperMode) {
            Mage::setIsDeveloperMode(true);
        }
    }

    /**
     * @return void
     */
    protected function _initMagento2()
    {
        $this->outputMagerunCompatibilityNotice('2');
    }

    /**
     * Show a hint that this is Magento incompatible with Magerun and how to obtain the correct Magerun for it
     *
     * @param string $version of Magento, "1" or "2", that is incompatible
     */
    private function outputMagerunCompatibilityNotice($version)
    {
        $file = $version === '2' ? $version : '';
        $magentoHint = <<<MAGENTOHINT
You are running a Magento $version.x instance. This version of n98-magerun is not compatible
with Magento $version.x. Please use n98-magerun$version (version $version) for this shop.

A current version of the software can be downloaded on github.

<info>Download with curl
------------------</info>

    <comment>curl -O https://files.magerun.net/n98-magerun$file.phar</comment>

<info>Download with wget
------------------</info>

    <comment>wget https://files.magerun.net/n98-magerun$file.phar</comment>

MAGENTOHINT;

        $output = new ConsoleOutput();

        /** @var $formatter FormatterHelper */
        $formatter = $this->getHelperSet()->get('formatter');

        $output->writeln(array(
            '',
            $formatter->formatBlock('Compatibility Notice', 'bg=blue;fg=white', true),
            '',
            $magentoHint,
        ));

        throw new RuntimeException('This version of n98-magerun is not compatible with Magento ' . $version);
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param array $initConfig
     * @param OutputInterface $output
     * @return ConfigurationLoader
     */
    public function getConfigurationLoader(array $initConfig, OutputInterface $output)
    {
        trigger_error(__METHOD__ . ' moved, use getConfig()->getLoader()', E_USER_DEPRECATED);

        unset($initConfig, $output);

        $loader = $this->config ? $this->config->getLoader() : $this->configurationLoaderInjected;

        if (!$loader) {
            throw new RuntimeException('ConfigurationLoader is not yet available, initialize it or Config first');
        }

        return $loader;
    }

    /**
     * @param ConfigurationLoader $configurationLoader
     *
     * @return $this
     */
    public function setConfigurationLoader(ConfigurationLoader $configurationLoader)
    {
        if ($this->config) {
            $this->config->setLoader($configurationLoader);
        } else {
            /* inject loader to be used later when config is created in */
            /* @see N98\Magento\Application::init */
            $this->configurationLoaderInjected = $configurationLoader;
        }

        return $this;
    }

    /**
     * @param OutputInterface $output
     */
    protected function _addOutputStyles(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('debug', new OutputFormatterStyle('magenta', 'white'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('red', 'yellow', array('bold')));
    }
}
