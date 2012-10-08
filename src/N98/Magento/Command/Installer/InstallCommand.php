<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use N98\Util\OperatingSystem;
use N98\Util\Database as DatabaseUtils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Install magento <comment>(experimental)</comment>')
        ;

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
        $this->selectMagentoVersion($input, $output);
        $this->chooseInstallationFolder($input, $output);
        $this->downloadMagento($input, $output);
        $this->createDatabase($output);
        $this->installSampleData($input, $output);
        $this->setDirectoryPermissions();
        $this->installMagento($input, $output, $this->config['installationFolder']);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \InvalidArgumentException
     */
    protected function selectMagentoVersion(InputInterface $input, OutputInterface $output)
    {
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

        $this->config['magentoVersionData'] = $this->commandConfig['magento-packages'][$type - 1];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function chooseInstallationFolder(InputInterface $input, OutputInterface $output)
    {
        $defaultFolder = './magento';
        $question[] = "<question>Enter installation folder:</question> [<comment>" . $defaultFolder . "</comment>]";

        $installationFolder = $this->getHelper('dialog')->askAndValidate($output, $question, function($folderName) {
            if (!is_dir($folderName)) {
                if (!mkdir($folderName)) {
                    throw new \InvalidArgumentException('Cannot create folder.');
                }
            } else {
                $folderName = trim($folderName, '. /');
                $folderName = realpath($folderName);
                return $folderName;
            }

            return $folderName;
        }, false, $defaultFolder);

        $this->config['installationFolder'] = $installationFolder;
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
                $this->config['installationFolder'],
                true
            );
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }

        return true;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function createDatabase(OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        /**
         * Database
         */
        do {
            $this->config['db_host'] = $dialog->askAndValidate($output, '<question>Please enter the database host:</question> <comment>[localhost]</comment>: ', $this->notEmptyCallback, false, 'localhost');
            $this->config['db_user'] = $dialog->askAndValidate($output, '<question>Please enter the database username:</question> ', $this->notEmptyCallback);
            $this->config['db_pass'] = $dialog->ask($output, '<question>Please enter the database password:</question> ');
            $this->config['db_name'] = $dialog->askAndValidate($output, '<question>Please enter the database name:</question> ', $this->notEmptyCallback);
            $db = $this->validateDatabaseSettings($output);
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
    protected function validateDatabaseSettings(OutputInterface $output)
    {
        try {
            $db = new \PDO('mysql:host='. $this->config['db_host'], $this->config['db_user'], $this->config['db_pass']);
            if (!$db->query('USE ' . $this->config['db_name'])) {
                $db->query("CREATE DATABASE `" . $this->config['db_name'] . "`");
                $output->writeln('<info>Created database ' . $this->config['db_name'] . '</info>');
                $db->query('USE ' . $this->config['db_name']);
            }

            return $db;
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
        $installSampleData = $dialog->askConfirmation($output, '<question>Install sample data?</question> <comment>[y]</comment>: ');
        if ($installSampleData) {
            foreach ($this->commandConfig['demo-data-packages'] as $demoPackageData) {
                if ($demoPackageData['name'] == $extra['sample-data']) {
                    $package = $this->downloadByComposerConfig(
                        $input,
                        $output,
                        $demoPackageData,
                        $this->config['installationFolder'],
                        false
                    );

                    $expandedFolder = $this->config['installationFolder']
                                    . DIRECTORY_SEPARATOR
                                    . str_replace(array('.tar.gz', '.tar.bz2', '.zip'), '', basename($package->getDistUrl()));
                    if (is_dir($expandedFolder)) {
                        $filesystem = new Filesystem();
                        $filesystem->recursiveCopy(
                            $expandedFolder,
                            $this->config['installationFolder']
                        );
                        $filesystem->recursiveRemoveDirectory($expandedFolder);
                    }

                    // Install sample data
                    $sampleDataSqlFile = glob($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'magento_*sample_data*sql');
                    $db = $this->config['db']; /* @var $db \PDO */
                    if (isset($sampleDataSqlFile[0])) {
                        $os = new OperatingSystem();
                        if ($os->isProgramInstalled('mysql')) {
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
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     */
    protected function installMagento(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $defaults = $this->commandConfig['installation']['defaults'];

        $sessionSave = $dialog->ask(
            $output,
            '<question>Please enter the session save:</question> <comment>[' . $defaults['session_save'] . ']</comment>: ',
            $defaults['session_save']
        );

        $adminFrontname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin frontname:</question> <comment>[' . $defaults['admin_frontname'] . ']</comment> ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_frontname']
        );

        $currency = $dialog->askAndValidate(
            $output,
            '<question>Please enter the default currency code:</question> <comment>[' . $defaults['currency'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['currency']
        );

        $locale = $dialog->askAndValidate(
            $output,
            '<question>Please enter the locale code:</question> <comment>[' . $defaults['locale'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['locale']
        );

        $timezone = $dialog->askAndValidate(
            $output,
            '<question>Please enter the timezone:</question> <comment>[' . $defaults['timezone'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['timezone']
        );

        $adminUsername = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin username:</question> <comment>[' . $defaults['admin_username'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_username']
        );

        $adminPassword = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin password:</question> <comment>[' . $defaults['admin_password'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_password']
        );

        $adminFirstname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s firstname:</question> <comment>[' . $defaults['admin_firstname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_firstname']
        );

        $adminLastname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s lastname:</question> <comment>[' . $defaults['admin_lastname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_lastname']
        );

        $adminEmail = $dialog->askAndValidate(
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
            return $input;
        };

        $baseUrl = $dialog->askAndValidate(
            $output,
            '<question>Please enter the base url:</question> ',
            $validateBaseUrl,
            false
        );
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl

        $_SERVER['argv']['license_agreement_accepted'] = 'yes';
        $_SERVER['argv']['locale'] = $locale;
        $_SERVER['argv']['timezone'] = $timezone;
        $_SERVER['argv']['db_host'] = $this->config['db_host'];
        $_SERVER['argv']['db_name'] = $this->config['db_name'];
        $_SERVER['argv']['db_user'] = $this->config['db_user'];
        $_SERVER['argv']['db_pass'] = $this->config['db_pass'];
        $_SERVER['argv']['url'] = $baseUrl;
        $_SERVER['argv']['use_rewrites'] = 'yes';
        $_SERVER['argv']['use_secure'] = 'no';
        $_SERVER['argv']['secure_base_url'] = '';
        $_SERVER['argv']['use_secure_admin'] = 'no';
        $_SERVER['argv']['admin_username'] = $adminUsername;
        $_SERVER['argv']['admin_lastname'] = $adminLastname;
        $_SERVER['argv']['admin_firstname'] = $adminFirstname;
        $_SERVER['argv']['admin_email'] = $adminEmail;
        $_SERVER['argv']['admin_password'] = $adminPassword;
        $_SERVER['argv']['session_save'] = $sessionSave;
        $_SERVER['argv']['admin_frontname'] = $adminFrontname;
        $_SERVER['argv']['default_currency'] = $currency;
        $_SERVER['argv']['skip_url_validation'] = 'yes';
        $this->replaceHtaccessFile($baseUrl);
        $output->writeln('<info>Start installation process.</info>');

        $dialog = $this->getHelperSet()->get('dialog');
        if ($dialog->askConfirmation($output, '<question>Write BaseURL to .htaccess file?</question> <comment>[n]</comment>: ', false)) {
            $this->replaceHtaccessFile($baseUrl);
        }

        $output->writeln('<info>Installing magento</info>');
        include($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'install.php');

        $output->writeln('<info>Successfully installed magento</info>');
    }

    /**
     * @return false|string
     */
    protected function validateBaseUrlCallback()
    {

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

    protected function setDirectoryPermissions()
    {
        $varFolder = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'var';
        @chmod($varFolder, 0777);

        $mediaFolder = $this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'media';
        @chmod($mediaFolder, 0777);

        $finder = new Finder();
        $finder->directories()
            ->in(array($varFolder, $mediaFolder));
        foreach ($finder as $dir) {
            @chmod($dir->getRealpath(), 0777);
        }
    }
}