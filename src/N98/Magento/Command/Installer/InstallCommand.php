<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\MagentoHelper;
use N98\Util\Database as DatabaseUtils;
use N98\Util\Filesystem;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Input\InputArgument;
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
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $commandConfig;

    protected $notEmptyCallback;

    protected function configure()
    {
        $this
            ->setName('install')
            ->addOption('magentoVersion', null, InputOption::VALUE_OPTIONAL, 'Magento version')
            ->addOption('magentoVersionByName', null, InputOption::VALUE_OPTIONAL, 'Magento version name instead of order number')
            ->addOption('installationFolder', null, InputOption::VALUE_OPTIONAL, 'Installation folder')
            ->addOption('dbHost', null, InputOption::VALUE_OPTIONAL, 'Database host')
            ->addOption('dbUser', null, InputOption::VALUE_OPTIONAL, 'Database user')
            ->addOption('dbPass', null, InputOption::VALUE_OPTIONAL, 'Database password')
            ->addOption('dbName', null, InputOption::VALUE_OPTIONAL, 'Database name')
            ->addOption('installSampleData', null, InputOption::VALUE_OPTIONAL, 'Install sample data')
            ->addOption('useDefaultConfigParams', null, InputOption::VALUE_OPTIONAL, 'Use default installation parameters defined in the yaml file')
            ->addOption('baseUrl', null, InputOption::VALUE_OPTIONAL, 'Installation base url')
            ->addOption('replaceHtaccessFile', null, InputOption::VALUE_OPTIONAL, 'Generate htaccess file (for non vhost environment)')
            ->addOption(
                'noDownload',
                null,
                InputOption::VALUE_NONE,
                'If set skips download step. Used when installationFolder is already a Magento installation that has ' .
                'to be installed on the given database.'
            )
            ->setDescription('Install magento')
        ;

        $help = <<<HELP
* Download Magento by a list of git repos and zip files (mageplus, magelte, official community packages).
* Try to create database if it does not exist.
* Installs Magento sample data if available (since version 1.2.0).
* Starts Magento installer
* Sets rewrite base in .htaccess file

Example of an unattended Magento CE 1.7.0.2 installation:

   $ n98-magerun.phar install --dbHost="localhost" --dbUser="mydbuser" --dbPass="mysecret" --dbName="magentodb" --installSampleData=yes --useDefaultConfigParams=yes --magentoVersionByName="magento-ce-1.7.0.2" --installationFolder="magento" --baseUrl="http://magento.localdomain/"

Additionally, with --noDownload option you can install Magento working copy already stored in --installationFolder on
the given database.

See it in action: http://youtu.be/WU-CbJ86eQc

HELP;
        $this->setHelp($help);

        $this->notEmptyCallback = function($input)
        {
            if (empty($input)) {
                throw new \InvalidArgumentException('Please enter a value');
            }
            return $input;
        };
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->commandConfig = $this->getCommandConfig();
        $this->writeSection($output, 'Magento Installation');
        if (!extension_loaded('pdo_mysql')) {
            throw new \RuntimeException('PHP extension pdo_mysql is required to start installation');
        }
        if (!$input->getOption('noDownload')) {
            $this->selectMagentoVersion($input, $output);
        }

        $this->chooseInstallationFolder($input, $output);

        if (!$input->getOption('noDownload')) {
            $this->downloadMagento($input, $output);
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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \InvalidArgumentException
     */
    protected function selectMagentoVersion(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('magentoVersion') == null && $input->getOption('magentoVersionByName') == null) {
            $question = array();
            foreach ($this->commandConfig['magento-packages'] as $key => $package) {
                $question[] = '<comment>' . str_pad('[' . ($key + 1) . ']', 4, ' ') . '</comment> ' . $package['name'] . "\n";
            }
            $question[] = "<question>Choose a magento version:</question> ";

            $commandConfig = $this->commandConfig;


            $type = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($commandConfig) {
                if (!in_array($typeInput, range(1, count($commandConfig['magento-packages'])))) {
                    throw new \InvalidArgumentException('Invalid type');
                }

                return $typeInput;
            });
        } else {
            $type = null;

            if ($input->getOption('magentoVersion')) {
                $type = $input->getOption('magentoVersion');
            } elseif ($input->getOption('magentoVersionByName')) {
                foreach ($this->commandConfig['magento-packages'] as $key => $package) {
                    if ($package['name'] == $input->getOption('magentoVersionByName')) {
                        $type = $key+1;
                        break;
                    }
                }
            }

            if ($type == null) {
                throw new \InvalidArgumentException('Unable to locate Magento version');
            }
        }

        $this->config['magentoVersionData'] = $this->commandConfig['magento-packages'][$type - 1];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function chooseInstallationFolder(InputInterface $input, OutputInterface $output)
    {
        $validateInstallationFolder = function($folderName) use ($input) {

            $folderName = rtrim(trim($folderName, ' '), '/');
            if (substr($folderName, 0, 1) == '.') {
                $cwd = \getcwd() ;
                if (empty($cwd) && isset($_SERVER['PWD'])) {
                    $cwd = $_SERVER['PWD'];
                }
                $folderName = $cwd . substr($folderName, 1);
            }

            if (empty($folderName)) {
                throw new \InvalidArgumentException('Installation folder cannot be empty');
            }

            if (!is_dir($folderName)) {
                if (!@mkdir($folderName,0777, true)) {
                    throw new \InvalidArgumentException('Cannot create folder.');
                }

                return $folderName;
            }

            if ($input->getOption('noDownload')) {
                /** @var MagentoHelper $magentoHelper */
                $magentoHelper = new MagentoHelper();
                $magentoHelper->detect($folderName);
                if ($magentoHelper->getRootFolder() !== $folderName) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Folder %s is not a Magento working copy.',
                            $folderName
                        )
                    );
                }

                $localXml = $folderName . '/app/etc/local.xml';
                if (file_exists($localXml)) {
                    throw new \InvalidArgumentException(
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

            $installationFolder = $this->getHelper('dialog')->askAndValidate($output, $question, $validateInstallationFolder, false, $defaultFolder);

        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);

        }

        $this->config['installationFolder'] = realpath($installationFolder);
        \chdir($this->config['installationFolder']);
    }

    protected function test($folderName) {

    }

    /**
     * @param array $magentoVersionData
     * @param string $installationFolder
     * @return bool
     */
    public function downloadMagento(InputInterface $input, OutputInterface $output) {
        try {
            $package = $this->createComposerPackageByConfig($this->config['magentoVersionData']);
            $this->config['magentoPackage'] = $package;

            if (file_exists($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
                $output->writeln('<error>A magento installation already exists in this folder </error>');
                return false;
            }

            $this->config['magentoPackage'] = $this->downloadByComposerConfig(
                $input,
                $output,
                $package,
                $this->config['installationFolder'] . '/_n98_magerun_download',
                true
            );

            $filesystem = new \Composer\Util\Filesystem();
            $filesystem->copyThenRemove($this->config['installationFolder'] . '/_n98_magerun_download', $this->config['installationFolder']);

            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                // Patch installer
                $this->patchMagentoInstallerForPHP54($this->config['installationFolder']);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }

        return true;
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
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function createDatabase(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        /**
         * Database
         */
        do {
            $this->config['db_host'] = $input->getOption('dbHost') !== null ? $input->getOption('dbHost') : $dialog->askAndValidate($output, '<question>Please enter the database host:</question> <comment>[localhost]</comment>: ', $this->notEmptyCallback, false, 'localhost');
            $this->config['db_user'] = $input->getOption('dbUser') !== null ? $input->getOption('dbUser') : $dialog->askAndValidate($output, '<question>Please enter the database username:</question> ', $this->notEmptyCallback);
            $this->config['db_pass'] = $input->hasParameterOption('--dbPass=' . $input->getOption('dbPass')) ? $input->getOption('dbPass') : $dialog->ask($output, '<question>Please enter the database password:</question> ');
            $this->config['db_name'] = $input->getOption('dbName') !== null ? $input->getOption('dbName') : $dialog->askAndValidate($output, '<question>Please enter the database name:</question> ', $this->notEmptyCallback);
            $db = $this->validateDatabaseSettings($output, $input);
        } while ($db === false);

        $this->config['db'] = $db;
    }

    /**
     * @param OutputInterface $output
     * @param string $dbHost
     * @param string $dbUser
     * @param string $dbPass
     * @param string $dbName
     * @return bool|PDO
     */
    protected function validateDatabaseSettings(OutputInterface $output, InputInterface $input)
    {
        try {
            $db = new \PDO('mysql:host='. $this->config['db_host'], $this->config['db_user'], $this->config['db_pass']);
            if (!$db->query('USE ' . $this->config['db_name'])) {
                $db->query("CREATE DATABASE `" . $this->config['db_name'] . "`");
                $output->writeln('<info>Created database ' . $this->config['db_name'] . '</info>');
                $db->query('USE ' . $this->config['db_name']);
                return $db;
            }

            if ($input->getOption('noDownload')) {
                $output->writeln("<error>Database {$this->config['db_name']} already exists.</error>");
                return false;
            }
        } catch (\PDOException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return false;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function installSampleData(InputInterface $input, OutputInterface $output)
    {
        $magentoPackage = $this->config['magentoPackage']; /* @var $magentoPackage \Composer\Package\MemoryPackage */
        $extra  = $magentoPackage->getExtra();
        if (!isset($extra['sample-data'])) {
            return;
        }

        $dialog = $this->getHelperSet()->get('dialog');

        $installSampleData = ($input->getOption('installSampleData') !== null) ? $this->_parseBoolOption($input->getOption('installSampleData')) : $dialog->askConfirmation($output, '<question>Install sample data?</question> <comment>[y]</comment>: ');

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
                    $sampleDataSqlFile = glob($this->config['installationFolder'] . '/_temp_demo_data/magento_*sample_data*sql');
                    $db = $this->config['db']; /* @var $db \PDO */
                    if (isset($sampleDataSqlFile[0])) {
                        if (OperatingSystem::isProgramInstalled('mysql')) {
                            $exec = 'mysql '
                                . '-h' . escapeshellarg(strval($this->config['db_host']))
                                . ' '
                                . '-u' . escapeshellarg(strval($this->config['db_user']))
                                . ' '
                                . (!strval($this->config['db_pass'] == '') ? '-p' . escapeshellarg($this->config['db_pass']) . ' ' : '')
                                . strval($this->config['db_name'])
                                . ' < '
                                . escapeshellarg($sampleDataSqlFile[0]);
                            $output->writeln('<info>Importing <comment>' . $sampleDataSqlFile[0] . '</comment> with mysql cli client</info>');
                            exec($exec);
                            @unlink($sampleDataSqlFile);
                        } else {
                            $output->writeln('<info>Importing <comment>' . $sampleDataSqlFile[0] . '</comment> with PDO driver</info>');
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

        $mediaFolder = $this->config['installationFolder'] . '/media';
        $wrongFolder = $this->config['installationFolder'] . '/_temp_demo_data/media';
        if (is_dir($wrongFolder)) {
            $filesystem->recursiveCopy(
                $wrongFolder,
                $mediaFolder
            );
            $filesystem->recursiveRemoveDirectory($wrongFolder);
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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     */
    protected function installMagento(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setAutoExit(false);
        $dialog = $this->getHelperSet()->get('dialog');

        $defaults = $this->commandConfig['installation']['defaults'];

        $useDefaultConfigParams = $this->_parseBoolOption($input->getOption('useDefaultConfigParams'));
        
        $sessionSave = $useDefaultConfigParams ? $defaults['session_save'] : $dialog->ask(
            $output,
            '<question>Please enter the session save:</question> <comment>[' . $defaults['session_save'] . ']</comment>: ',
            $defaults['session_save']
        );

        $adminFrontname = $useDefaultConfigParams ? $defaults['admin_frontname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin frontname:</question> <comment>[' . $defaults['admin_frontname'] . ']</comment> ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_frontname']
        );

        $currency = $useDefaultConfigParams ? $defaults['currency'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the default currency code:</question> <comment>[' . $defaults['currency'] . ']</comment>: ',
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
            '<question>Please enter the admin username:</question> <comment>[' . $defaults['admin_username'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_username']
        );

        $adminPassword = $useDefaultConfigParams ? $defaults['admin_password'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin password:</question> <comment>[' . $defaults['admin_password'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_password']
        );

        $adminFirstname = $useDefaultConfigParams ? $defaults['admin_firstname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s firstname:</question> <comment>[' . $defaults['admin_firstname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_firstname']
        );

        $adminLastname = $useDefaultConfigParams ? $defaults['admin_lastname'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s lastname:</question> <comment>[' . $defaults['admin_lastname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_lastname']
        );

        $adminEmail = $useDefaultConfigParams ? $defaults['admin_email'] : $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s email:</question> <comment>[' . $defaults['admin_email'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_email']
        );

        $validateBaseUrl = function($input) {
            if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $input)) {
                throw new \InvalidArgumentException('Please enter a valid URL');
            }
            if (strstr($input, 'localhost')) {
                throw new \InvalidArgumentException('localhost cause problems! Please use 127.0.0.1 or another hostname');
            }
            return $input;
        };

        $baseUrl = ($input->getOption('baseUrl') !== null) ? $input->getOption('baseUrl') : $dialog->askAndValidate(
            $output,
            '<question>Please enter the base url:</question> ',
            $validateBaseUrl,
            false
        );
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl

        $argv = array(
            'license_agreement_accepted' => 'yes',
            'locale'                     => $locale,
            'timezone'                   => $timezone,
            'db_host'                    => $this->config['db_host'],
            'db_name'                    => $this->config['db_name'],
            'db_user'                    => $this->config['db_user'],
            'db_pass'                    => $this->config['db_pass'],
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
        $installArgs = '';
        foreach ($argv as $argName => $argValue) {
            $installArgs .= '--' . $argName . ' ' . escapeshellarg($argValue) . ' ';
        }

        $output->writeln('<info>Start installation process.</info>');

        if (OperatingSystem::isWindows()) {
            $installCommand = 'php ' . $this->getInstallScriptPath() . ' ' . $installArgs;
        } else {
            $installCommand = '/usr/bin/env php ' . $this->getInstallScriptPath() . ' ' . $installArgs;
        }
        $output->writeln('<comment>' . $installCommand . '</comment>');
        exec($installCommand);

        $dialog = $this->getHelperSet()->get('dialog');

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
                false)
            ) {
                $replaceHtaccessFile = true;
            }

            if ($replaceHtaccessFile) {
                $this->replaceHtaccessFile($baseUrl);
            }
        }

        \chdir($this->config['installationFolder']);
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
        $magento1InstallScriptPath  = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'install.php';
        $magento2InstallScriptPath  = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'dev/shell/install.php';
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
        copy($this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess', $this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess.dist');
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($this->config['installationFolder'] . DIRECTORY_SEPARATOR . '.htaccess', $content);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
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
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}