<?php

namespace N98\Magento\Command\Installer;

use Composer\Composer;
use Composer\Package\CompletePackage;
use Exception;
use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\BinaryString;
use N98\Util\Database as DatabaseUtils;
use N98\Util\Exec;
use N98\Util\Filesystem;
use N98\Util\OperatingSystem;
use N98\Util\VerifyOrDie;
use PDO;
use PDOException;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class InstallCommand
 *
 * @codeCoverageIgnore  - Travis server uses installer to create a new shop. If it not works complete build fails.
 * @package N98\Magento\Command\Installer
 */
class InstallCommand extends AbstractMagentoCommand
{
    /**
     * @deprecated since since 1.97.22; Use constant from Exec-Utility instead
     * @see Exec::CODE_CLEAN_EXIT
     */
    const EXEC_STATUS_OK = 0;

    const DEFAULT_SESSION_PATH = 'var/session';

    const MAGENTO_INSTALL_SCRIPT_PATH = 'install.php';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $_argv;

    /**
     * @var array
     */
    protected $commandConfig;

    /**
     * @var \Closure
     */
    protected $notEmptyCallback;

    protected function configure()
    {
        $this
            ->setName('install')
            ->addOption('magentoVersion', null, InputOption::VALUE_OPTIONAL, 'Magento version')
            ->addOption(
                'magentoVersionByName',
                null,
                InputOption::VALUE_OPTIONAL,
                'Magento version name instead of order number'
            )
            ->addOption('installationFolder', null, InputOption::VALUE_OPTIONAL, 'Installation folder')
            ->addOption('dbHost', null, InputOption::VALUE_OPTIONAL, 'Database host')
            ->addOption('dbUser', null, InputOption::VALUE_OPTIONAL, 'Database user')
            ->addOption('dbPass', null, InputOption::VALUE_OPTIONAL, 'Database password')
            ->addOption('dbName', null, InputOption::VALUE_OPTIONAL, 'Database name')
            ->addOption('dbPort', null, InputOption::VALUE_OPTIONAL, 'Database port', 3306)
            ->addOption('dbPrefix', null, InputOption::VALUE_OPTIONAL, 'Table prefix', '')
            ->addOption('installSampleData', null, InputOption::VALUE_OPTIONAL, 'Install sample data')
            ->addOption(
                'useDefaultConfigParams',
                null,
                InputOption::VALUE_OPTIONAL,
                'Use default installation parameters defined in the yaml file'
            )->addOption('baseUrl', null, InputOption::VALUE_OPTIONAL, 'Installation base url')
            ->addOption(
                'replaceHtaccessFile',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate htaccess file (for non vhost environment)'
            )->addOption(
                'noDownload',
                null,
                InputOption::VALUE_NONE,
                'If set skips download step. Used when installationFolder is already a Magento installation that has ' .
                'to be installed on the given database.'
            )
            ->addOption(
                'only-download',
                null,
                InputOption::VALUE_NONE,
                'Downloads (and extracts) source code'
            )
            ->addOption(
                'forceUseDb',
                null,
                InputOption::VALUE_NONE,
                'If --noDownload passed, force to use given database if it already exists.'
            )->setDescription('Install magento');

        $help = <<<HELP
* Download Magento by a list of git repos and zip files (mageplus,
  magelte, official community packages).
* Try to create database if it does not exist.
* Installs Magento sample data if available (since version 1.2.0).
* Starts Magento installer
* Sets rewrite base in .htaccess file

Example of an unattended Magento CE/ Open Source 1.9.3.10 installation:

   $ n98-magerun.phar install --dbHost="localhost" --dbUser="mydbuser" \
     --dbPass="mysecret" --dbName="magentodb" --installSampleData=yes \
     --useDefaultConfigParams=yes \
     --magentoVersionByName="magento-mirror-1.9.3.10" \
     --installationFolder="magento" --baseUrl="http://magento.localdomain/"

(Magento is only freely available via Github with Magerun, it uses the best
community mirror)

Additionally, with --noDownload option you can install Magento working
copy already stored in --installationFolder on the given database.

See it in action: http://youtu.be/WU-CbJ86eQc

HELP;
        $this->setHelp($help);

        $this->notEmptyCallback = function ($input) {
            if (empty($input)) {
                throw new InvalidArgumentException('Please enter a value');
            }

            return $input;
        };
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Exec::allowed();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->commandConfig = $this->getCommandConfig();
        $this->writeSection($output, 'Magento Installation');

        $this->precheckPhp();

        if (!$input->getOption('noDownload')) {
            $this->selectMagentoVersion($input, $output);
        }

        $this->chooseInstallationFolder($input, $output);

        if (!$input->getOption('noDownload')) {
            $result = $this->downloadMagento($input, $output);

            if ($result === false) {
                return 1;
            }
        }

        if ($input->getOption('only-download')) {
            return 0;
        }

        $this->createDatabase($input, $output);

        if (!$input->getOption('noDownload')) {
            $this->installSampleData($input, $output);
        }

        $this->removeEmptyFolders();
        $this->setDirectoryPermissions($output);
        $this->installMagento($input, $output, $this->config['installationFolder']);
    }

    /**
     * Check PHP environment agains minimal required settings modules
     */
    protected function precheckPhp()
    {
        $extensions = $this->commandConfig['installation']['pre-check']['php']['extensions'];
        $missingExtensions = array();
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if (count($missingExtensions) > 0) {
            throw new RuntimeException(
                'The following PHP extensions are required to start installation: ' . implode(',', $missingExtensions)
            );
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws InvalidArgumentException
     */
    protected function selectMagentoVersion(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('magentoVersion') == null && $input->getOption('magentoVersionByName') == null) {
            $question = array();
            foreach ($this->commandConfig['magento-packages'] as $key => $package) {
                $question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ') . '</comment> ' .
                    $package['name'] . "\n";
            }
            $question[] = "<question>Choose a magento version:</question> ";

            $commandConfig = $this->commandConfig;

            $type = $this->getHelper('dialog')->askAndValidate(
                $output,
                $question,
                function ($typeInput) use ($commandConfig) {
                    if (!in_array($typeInput, range(1, count($commandConfig['magento-packages'])))) {
                        throw new InvalidArgumentException('Invalid type');
                    }

                    return $typeInput;
                }
            );
        } else {
            $type = null;

            if ($input->getOption('magentoVersion')) {
                $type = $input->getOption('magentoVersion');
                if ($type !== (string) (int) $type) {
                    $type = $this->getPackageNumberByName($type);
                }
            } elseif ($input->getOption('magentoVersionByName')) {
                $type = $this->getPackageNumberByName($input->getOption('magentoVersionByName'));
            }

            if ($type == null) {
                throw new InvalidArgumentException('Unable to locate Magento version');
            }
        }

        $magentoPackages = $this->commandConfig['magento-packages'];

        $index = $type - 1;
        if (!isset($magentoPackages[$index])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid Magento package number %s, must be from 1 to %d.',
                    var_export($type, true),
                    count($magentoPackages)
                )
            );
        }

        $this->config['magentoVersionData'] = $magentoPackages[$index];
    }

    /**
     * @param $name
     *
     * @return int 1 or greater as the one-based package number, null on failure to resolve the name
     */
    private function getPackageNumberByName($name)
    {
        // directly filter integer strings
        if ($name === (string) (int) $name) {
            return (int) $name;
        }

        $magentoPackages = $this->commandConfig['magento-packages'];

        foreach ($magentoPackages as $key => $package) {
            if ($package['name'] === $name) {
                return $key + 1;
            }
        }

        return;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    public function downloadMagento(InputInterface $input, OutputInterface $output)
    {
        try {
            $package = $this->createComposerPackageByConfig($this->config['magentoVersionData']);
            $this->config['magentoPackage'] = $package;

            $installationFolder = $this->config['installationFolder'];

            if (file_exists($installationFolder . '/app/Mage.php')) {
                $output->writeln(
                    sprintf(
                        '<error>A magento installation already exists in this folder "%s"</error>',
                        $installationFolder
                    )
                );

                return false;
            }

            $composer = $this->getComposer($input, $output);
            $targetFolder = $this->getTargetFolderByType($composer, $package, $installationFolder);
            $this->config['magentoPackage'] = $this->downloadByComposerConfig(
                $input,
                $output,
                $package,
                $targetFolder,
                true
            );

            if ($this->isSourceTypeRepository($package->getSourceType())) {
                $filesystem = new \N98\Util\Filesystem;
                $filesystem->recursiveCopy($targetFolder, $installationFolder, array('.git', '.hg'));
            } else {
                $filesystem = new \Composer\Util\Filesystem();
                $filesystem->copyThenRemove(
                    $installationFolder . '/_n98_magerun_download',
                    $installationFolder
                );
            }

            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                // Patch installer
                $this->patchMagentoInstallerForPHP54($installationFolder);
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return false;
        }

        return true;
    }

    /**
     * construct a folder to where magerun will download the source to, cache git/hg repositories under COMPOSER_HOME
     *
     * @param Composer $composer
     * @param CompletePackage $package
     * @param $installationFolder
     *
     * @return string
     */
    protected function getTargetFolderByType(Composer $composer, CompletePackage $package, $installationFolder)
    {
        $type = $package->getSourceType();
        if ($this->isSourceTypeRepository($type)) {
            $targetPath = sprintf(
                '%s/%s/%s/%s',
                $composer->getConfig()->get('cache-dir'),
                '_n98_magerun_download',
                $type,
                preg_replace('{[^a-z0-9.]}i', '-', $package->getSourceUrl())
            );
        } else {
            $targetPath = sprintf(
                '%s/%s',
                $installationFolder,
                '_n98_magerun_download'
            );
        }

        return $targetPath;
    }

    /**
     * @param string $magentoFolder
     */
    protected function patchMagentoInstallerForPHP54($magentoFolder)
    {
        $installerConfig = $magentoFolder
            . DIRECTORY_SEPARATOR
            . 'app/code/core/Mage/Install/etc/config.xml';
        if (file_exists($installerConfig)) {
            $xml = file_get_contents($installerConfig);
            file_put_contents($installerConfig, str_replace('<pdo_mysql/>', '<pdo_mysql>1</pdo_mysql>', $xml));
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws InvalidArgumentException
     */
    protected function createDatabase(InputInterface $input, OutputInterface $output)
    {
        $dbOptions = array('--dbHost', '--dbUser', '--dbPass', '--dbName');
        $dbOptionsFound = 0;
        foreach ($dbOptions as $dbOption) {
            foreach ($this->getCliArguments() as $definedCliOption) {
                if (BinaryString::startsWith($definedCliOption, $dbOption)) {
                    $dbOptionsFound++;
                }
            }
        }

        $hasAllOptions = $dbOptionsFound == 4;

        // if all database options were passed in at cmd line
        if ($hasAllOptions) {
            $this->config['db_host'] = $input->getOption('dbHost');
            $this->config['db_user'] = $input->getOption('dbUser');
            $this->config['db_pass'] = $input->getOption('dbPass');
            $this->config['db_name'] = VerifyOrDie::filename(
                $input->getOption('dbName'),
                'Database name is not portable'
            );
            $this->config['db_port'] = $input->getOption('dbPort');
            $this->config['db_prefix'] = $input->getOption('dbPrefix');
            $db = $this->validateDatabaseSettings($output, $input);

            if ($db === false) {
                throw new InvalidArgumentException("Database configuration is invalid");
            }
        } else {
            /** @var DialogHelper $dialog */
            $dialog = $this->getHelper('dialog');
            do {
                $dbHostDefault = $input->getOption('dbHost') ? $input->getOption('dbHost') : 'localhost';
                $this->config['db_host'] = $dialog->askAndValidate(
                    $output,
                    '<question>Please enter the database host</question> <comment>[' . $dbHostDefault . ']</comment>: ',
                    $this->notEmptyCallback,
                    false,
                    $dbHostDefault
                );

                $dbUserDefault = $input->getOption('dbUser') ? $input->getOption('dbUser') : 'root';
                $this->config['db_user'] = $dialog->askAndValidate(
                    $output,
                    '<question>Please enter the database username</question> <comment>[' . $dbUserDefault .
                    ']</comment>: ',
                    $this->notEmptyCallback,
                    false,
                    $dbUserDefault
                );

                $dbPassDefault = $input->getOption('dbPass') ? $input->getOption('dbPass') : '';
                $this->config['db_pass'] = $dialog->ask(
                    $output,
                    '<question>Please enter the database password</question> <comment>[' . $dbPassDefault .
                    ']</comment>: ',
                    $dbPassDefault
                );

                $dbNameDefault = $input->getOption('dbName') ? $input->getOption('dbName') : 'magento';
                $this->config['db_name'] = $dialog->askAndValidate(
                    $output,
                    '<question>Please enter the database name</question> <comment>[' . $dbNameDefault . ']</comment>: ',
                    $this->notEmptyCallback,
                    false,
                    $dbNameDefault
                );

                $dbPortDefault = $input->getOption('dbPort') ? $input->getOption('dbPort') : 3306;
                $this->config['db_port'] = $dialog->askAndValidate(
                    $output,
                    '<question>Please enter the database port </question> <comment>[' . $dbPortDefault .
                    ']</comment>: ',
                    $this->notEmptyCallback,
                    false,
                    $dbPortDefault
                );

                $dbPrefixDefault = $input->getOption('dbPrefix') ? $input->getOption('dbPrefix') : '';
                $this->config['db_prefix'] = $dialog->ask(
                    $output,
                    '<question>Please enter the table prefix</question> <comment>[' . $dbPrefixDefault . ']</comment>:',
                    $dbPrefixDefault
                );
                $db = $this->validateDatabaseSettings($output, $input);
            } while ($db === false);
        }

        $this->config['db'] = $db;
    }

    /**
     * @param OutputInterface $output
     * @param InputInterface $input
     *
     * @return bool|PDO
     */
    protected function validateDatabaseSettings(OutputInterface $output, InputInterface $input)
    {
        try {
            $dsn = sprintf("mysql:host=%s;port=%s", $this->config['db_host'], $this->config['db_port']);
            $db = new PDO($dsn, $this->config['db_user'], $this->config['db_pass']);
            if (!$db->query('USE ' . $this->config['db_name'])) {
                $db->query("CREATE DATABASE `" . $this->config['db_name'] . "`");
                $output->writeln('<info>Created database ' . $this->config['db_name'] . '</info>');
                $db->query('USE ' . $this->config['db_name']);

                return $db;
            }
        } catch (PDOException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }

        if ($input->getOption('noDownload') && !$input->getOption('forceUseDb')) {
            $output->writeln(
                sprintf(
                    "<error>Database '%s' already exists, use --forceUseDb in combination with --noDownload" .
                    " to use an existing database</error>",
                    $this->config['db_name']
                )
            );
            return false;
        }

        return $db;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function installSampleData(InputInterface $input, OutputInterface $output)
    {
        $magentoPackage = $this->config['magentoPackage'];
        /* @var $magentoPackage \Composer\Package\MemoryPackage */
        $extra = $magentoPackage->getExtra();
        if (!isset($extra['sample-data'])) {
            return;
        }

        $dialog = $this->getHelper('dialog');

        $installSampleData = ($input->getOption('installSampleData') !== null)
            ? $this->_parseBoolOption($input->getOption('installSampleData'))
            : $dialog->askConfirmation(
                $output,
                '<question>Install sample data?</question> <comment>[y]</comment>: '
            );

        if ($installSampleData) {
            $filesystem = new Filesystem();

            foreach ($this->commandConfig['demo-data-packages'] as $demoPackageData) {
                if ($demoPackageData['name'] == $extra['sample-data']) {
                    $package = $this->downloadByComposerConfig(
                        $input,
                        $output,
                        $demoPackageData,
                        $this->config['installationFolder'] . '/_temp_demo_data',
                        false
                    );

                    $this->_fixComposerExtractionBug();

                    $expandedFolder = $this->config['installationFolder']
                        . '/_temp_demo_data/'
                        . str_replace(array('.tar.gz', '.tar.bz2', '.zip'), '', basename($package->getDistUrl()));
                    if (is_dir($expandedFolder)) {
                        $filesystem->recursiveCopy(
                            $expandedFolder,
                            $this->config['installationFolder']
                        );
                        $filesystem->recursiveRemoveDirectory($expandedFolder);
                    }

                    // Remove empty folder
                    if (is_dir($this->config['installationFolder'] . '/vendor/composer')) {
                        $filesystem->recursiveRemoveDirectory($this->config['installationFolder'] . '/vendor/composer');
                    }

                    // Install sample data
                    $sampleDataSqlFile = glob(
                        $this->config['installationFolder'] . '/_temp_demo_data/magento_*sample_data*sql'
                    );
                    $db = $this->config['db'];
                    /* @var $db PDO */
                    if (isset($sampleDataSqlFile[0])) {
                        if (OperatingSystem::isProgramInstalled('mysql')) {
                            $exec = 'mysql '
                                . '-h' . escapeshellarg(strval($this->config['db_host']))
                                . ' '
                                . '-u' . escapeshellarg(strval($this->config['db_user']))
                                . ' '
                                . ($this->config['db_port'] != '3306'
                                    ? '-P' . escapeshellarg($this->config['db_port']) . ' ' : '')
                                . (!strval($this->config['db_pass'] == '')
                                    ? '-p' . escapeshellarg($this->config['db_pass']) . ' ' : '')
                                . strval($this->config['db_name'])
                                . ' < '
                                . escapeshellarg($sampleDataSqlFile[0]);
                            $output->writeln(
                                '<info>Importing <comment>' . $sampleDataSqlFile[0] .
                                '</comment> with mysql cli client</info>'
                            );
                            Exec::run($exec);
                            @unlink($sampleDataSqlFile[0]);
                        } else {
                            $output->writeln(
                                '<info>Importing <comment>' . $sampleDataSqlFile[0] .
                                '</comment> with PDO driver</info>'
                            );
                            // Fallback -> Try to install dump file by PDO driver
                            $dbUtils = new DatabaseUtils();
                            $dbUtils->importSqlDump($db, $sampleDataSqlFile[0]);
                        }
                    }
                }
            }

            if (is_dir($this->config['installationFolder'] . '/_temp_demo_data')) {
                $filesystem->recursiveRemoveDirectory($this->config['installationFolder'] . '/_temp_demo_data');
            }
        }
    }

    protected function _fixComposerExtractionBug()
    {
        $filesystem = new Filesystem();
        foreach (array('/_temp_demo_data/media' => '/media', '/_temp_demo_data/skin' => '/skin') as $wrong => $right) {
            $wrongFolder = $this->config['installationFolder'] . $wrong;
            $rightFolder = $this->config['installationFolder'] . $right;
            if (is_dir($wrongFolder)) {
                $filesystem->recursiveCopy(
                    $wrongFolder,
                    $rightFolder
                );
                $filesystem->recursiveRemoveDirectory($wrongFolder);
            }
        }
    }

    /**
     * Remove empty composer extraction folder
     */
    protected function removeEmptyFolders()
    {
        if (is_dir(getcwd() . '/vendor')) {
            $finder = new Finder();
            $finder->files()->depth(3)->in(getcwd() . '/vendor');
            if ($finder->count() == 0) {
                $filesystem = new Filesystem();
                $filesystem->recursiveRemoveDirectory(getcwd() . '/vendor');
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array
     * @throws InvalidArgumentException parameter mismatch (e.g. base-url components like hostname)
     * @throws RuntimeException
     */
    protected function installMagento(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setAutoExit(false);
        /** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
        $dialog = $this->getHelper('dialog');

        $defaults = $this->commandConfig['installation']['defaults'];

        $useDefaultConfigParams = $this->_parseBoolOption($input->getOption('useDefaultConfigParams'));

        $sessionSave = $useDefaultConfigParams ? $defaults['session_save'] : $dialog->ask(
            $output,
            '<question>Please enter the session save:</question> <comment>[' .
            $defaults['session_save'] . ']</comment>: ',
            $defaults['session_save']
        );

        $adminFrontname = $useDefaultConfigParams ? $defaults['admin_frontname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin frontname:</question> <comment>[' .
            $defaults['admin_frontname'] . ']</comment> ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_frontname']
        );

        $currency = $useDefaultConfigParams ? $defaults['currency'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the default currency code:</question> <comment>[' .
            $defaults['currency'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['currency']
        );

        $locale = $useDefaultConfigParams ? $defaults['locale'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the locale code:</question> <comment>[' . $defaults['locale'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['locale']
        );

        $timezone = $useDefaultConfigParams ? $defaults['timezone'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the timezone:</question> <comment>[' . $defaults['timezone'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['timezone']
        );

        $adminUsername = $useDefaultConfigParams ? $defaults['admin_username'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin username:</question> <comment>[' .
            $defaults['admin_username'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_username']
        );

        $adminPassword = $useDefaultConfigParams ? $defaults['admin_password'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin password:</question> <comment>[' .
            $defaults['admin_password'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_password']
        );

        $adminFirstname = $useDefaultConfigParams ? $defaults['admin_firstname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s firstname:</question> <comment>[' .
            $defaults['admin_firstname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_firstname']
        );

        $adminLastname = $useDefaultConfigParams ? $defaults['admin_lastname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s lastname:</question> <comment>[' .
            $defaults['admin_lastname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_lastname']
        );

        $adminEmail = $useDefaultConfigParams ? $defaults['admin_email'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s email:</question> <comment>[' .
            $defaults['admin_email'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_email']
        );

        $validateBaseUrl = function ($input) {
            if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $input)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid URL %s. Please enter a valid URL', var_export($input, true))
                );
            }
            if (parse_url($input, \PHP_URL_HOST) == 'localhost') {
                throw new InvalidArgumentException(
                    'localhost cause problems! Please use 127.0.0.1 or another hostname'
                );
            }

            return $input;
        };

        $baseUrl = $input->getOption('baseUrl');
        if (null === $baseUrl) {
            if (!$input->isInteractive()) {
                throw new InvalidArgumentException('Installation base url is mandatory, use --baseUrl.');
            }
            $baseUrl = $dialog->askAndValidate(
                $output,
                '<question>Please enter the base url:</question> ',
                $validateBaseUrl
            );
        }
        $validateBaseUrl($baseUrl);
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl

        /**
         * Correct session save (common mistake)
         */
        if ($sessionSave == 'file') {
            $sessionSave = 'files';
        }

        /**
         * Try to create session folder
         */
        $defaultSessionFolder = $this->config['installationFolder'] . '/' . self::DEFAULT_SESSION_PATH;
        if ($sessionSave == 'files' && !is_dir($defaultSessionFolder)) {
            @mkdir($defaultSessionFolder);
        }

        $dbHost = $this->config['db_host'];
        if ($this->config['db_port'] != 3306) {
            $dbHost .= ':' . $this->config['db_port'];
        }

        $argv = array(
            'license_agreement_accepted' => 'yes',
            'locale'                     => $locale,
            'timezone'                   => $timezone,
            'db_host'                    => $dbHost,
            'db_name'                    => $this->config['db_name'],
            'db_user'                    => $this->config['db_user'],
            'db_pass'                    => $this->config['db_pass'],
            'db_prefix'                  => $this->config['db_prefix'],
            'url'                        => $baseUrl,
            'use_rewrites'               => 'yes',
            'use_secure'                 => 'no',
            'secure_base_url'            => '',
            'use_secure_admin'           => 'no',
            'admin_username'             => $adminUsername,
            'admin_lastname'             => $adminLastname,
            'admin_firstname'            => $adminFirstname,
            'admin_email'                => $adminEmail,
            'admin_password'             => $adminPassword,
            'session_save'               => $sessionSave,
            'admin_frontname'            => $adminFrontname, /* magento 1 */
            'backend_frontname'          => $adminFrontname, /* magento 2 */
            'default_currency'           => $currency,
            'skip_url_validation'        => 'yes',
        );
        if ($useDefaultConfigParams) {
            if (strlen($defaults['encryption_key']) > 0) {
                $argv['encryption_key'] = $defaults['encryption_key'];
            }
            if (strlen($defaults['use_secure']) > 0) {
                $argv['use_secure'] = $defaults['use_secure'];
                $argv['secure_base_url'] = str_replace('http://', 'https://', $baseUrl);
            }
            if (strlen($defaults['use_rewrites']) > 0) {
                $argv['use_rewrites'] = $defaults['use_rewrites'];
            }
        }

        $this->runInstallScriptCommand($output, $this->config['installationFolder'], $argv);

        /* @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');

        /**
         * Htaccess file
         */
        if ($input->getOption('useDefaultConfigParams') == null || $input->getOption('replaceHtaccessFile') != null) {
            $replaceHtaccessFile = false;

            if ($this->_parseBoolOption($input->getOption('replaceHtaccessFile'))) {
                $replaceHtaccessFile = true;
            } elseif ($dialog->askConfirmation(
                $output,
                '<question>Write BaseURL to .htaccess file?</question> <comment>[n]</comment>: ',
                false
            )
            ) {
                $replaceHtaccessFile = true;
            }

            if ($replaceHtaccessFile) {
                $this->replaceHtaccessFile($baseUrl);
            }
        }

        \chdir($this->config['installationFolder']);
        $this->getApplication()->reinit();
        $output->writeln('<info>Reindex all after installation</info>');
        $this->getApplication()->run(new StringInput('index:reindex:all'), $output);
        $this->getApplication()->run(new StringInput('sys:check'), $output);
        $output->writeln('<info>Successfully installed magento</info>');
    }

    /**
     * Check if we have a magento 2 or 1 installation and return path to install.php
     *
     * @return string
     */
    protected function getInstallScriptPath()
    {
        $magento1InstallScriptPath = $this->config['installationFolder'] . '/' . MAGENTO_INSTALL_SCRIPT_PATH;
        $magento2InstallScriptPath = $this->config['installationFolder'] . '/dev/shell/install.php';
        if (file_exists($magento2InstallScriptPath)) {
            return $magento2InstallScriptPath;
        }

        return $magento1InstallScriptPath;
    }

    /**
     * @param string $baseUrl
     */
    protected function replaceHtaccessFile($baseUrl)
    {
        $content = file_get_contents($this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess');
        copy(
            $this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess',
            $this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess.dist'
        );
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess', $content);
    }

    /**
     * @param OutputInterface $output
     */
    protected function setDirectoryPermissions($output)
    {
        try {
            $varFolder = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'var';
            if (!is_dir($varFolder)) {
                @mkdir($varFolder);
            }
            @chmod($varFolder, 0777);

            $varCacheFolder = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'var/cache';
            if (!is_dir($varCacheFolder)) {
                @mkdir($varCacheFolder);
            }
            @chmod($varCacheFolder, 0777);

            $mediaFolder = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'media';
            if (!is_dir($mediaFolder)) {
                @mkdir($mediaFolder);
            }
            @chmod($mediaFolder, 0777);

            $finder = Finder::create();
            $finder->directories()
                ->ignoreUnreadableDirs(true)
                ->in(array($varFolder, $mediaFolder));
            foreach ($finder as $dir) {
                @chmod($dir->getRealpath(), 0777);
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @return array
     */
    public function getCliArguments()
    {
        if ($this->_argv === null) {
            $this->_argv = $_SERVER['argv'];
        }

        return $this->_argv;
    }

    /**
     * @param array $args
     */
    public function setCliArguments($args)
    {
        $this->_argv = $args;
    }

    /**
     * Invoke Magento PHP install script shell/install.php
     *
     * @param OutputInterface $output
     * @param string $installationFolder folder where magento is installed in, must exists setup script in
     * @param array $argv
     * @return void
     */
    private function runInstallScriptCommand(OutputInterface $output, $installationFolder, array $argv)
    {
        $installArgs = '';
        foreach ($argv as $argName => $argValue) {
            $installArgs .= '--' . $argName . ' ' . escapeshellarg($argValue) . ' ';
        }

        $output->writeln('<info>Start installation process.</info>');

        $installCommand = sprintf(
            '%s -ddisplay_startup_errors=1 -ddisplay_errors=1 -derror_reporting=-1 -f %s -- %s',
            OperatingSystem::getPhpBinary(),
            escapeshellarg($installationFolder . '/' . self::MAGENTO_INSTALL_SCRIPT_PATH),
            $installArgs
        );

        $output->writeln('<comment>' . $installCommand . '</comment>');
        $installException = null;
        $installationOutput = null;
        $returnStatus = null;
        try {
            Exec::run($installCommand, $installationOutput, $returnStatus);
        } catch (Exception $installException) {
            /* fall-through intended */
        }

        if (isset($installException) || $returnStatus !== Exec::CODE_CLEAN_EXIT) {
            $this->getApplication()->setAutoExit(true);
            throw new RuntimeException(
                sprintf('Installation failed (Exit code %s). %s', $returnStatus, $installationOutput),
                1,
                $installException
            );
        }
        $output->writeln('<info>Successfully installed Magento</info>');
        $encryptionKey = trim(substr(strstr($installationOutput, ':'), 1));
        $output->writeln('<comment>Encryption Key:</comment> <info>' . $encryptionKey . '</info>');
    }
}
