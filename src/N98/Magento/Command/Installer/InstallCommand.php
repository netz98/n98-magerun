<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;

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
        $this->chooseInstalltionFolder($input, $output);
        $this->downloadMagento($input, $output);
        $this->createDatabase($output);
        $this->installSampleData($input, $output);
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
            $question[] = '<comment>[' . ($key+1) . ']</comment> ' . $package['name'] . "\n";
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
    protected function chooseInstalltionFolder(InputInterface $input, OutputInterface $output)
    {
        $defaultFolder = './magento';
        $question[] = "<question>Enter installation folder:</question> [<comment>" . $defaultFolder . "</comment>]";

        $installationFolder = $this->getHelper('dialog')->askAndValidate($output, $question, function($folderName) {
            if (!is_dir($folderName)) {
                if (!mkdir($folderName)) {
                    throw new \InvalidArgumentException('Cannot create folder.');
                }
            } else {
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
            $packageLoader = new PackageLoader();
            $package = $packageLoader->load($this->config['magentoVersionData']);
            $this->config['magentoPackage'] = $package;

            if (file_exists($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
                $output->writeln('<error>A magento installation already exists in this folder </error>');
                return false;
            }

            $io = new ConsoleIO($input, $output, $this->getHelperSet());
            $composer = ComposerFactory::create($io, array());
            $dm = $composer->getDownloadManager();
            $dm->download($package, $this->config['installationFolder'], true);
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
            $this->config['db_host'] = $dialog->askAndValidate($output, '<question>Please enter the database host</question> <comment>[localhost]</comment>: ', $this->notEmptyCallback, false, 'localhost');
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
                    $packageLoader = new PackageLoader();
                    $package = $packageLoader->load($demoPackageData);

                    $io = new ConsoleIO($input, $output, $this->getHelperSet());
                    $composer = ComposerFactory::create($io, array());
                    $dm = $composer->getDownloadManager();
                    $dm->download($package, $this->config['installationFolder'], true);

                    $expandedFolder = $this->config['installationFolder']
                                    . DIRECTORY_SEPARATOR
                                    . str_replace('.tar.gz', '', basename($package->getDistUrl()));
                    if (is_dir($expandedFolder)) {
                        $filesystem = new Filesystem();
                        $filesystem->recursiveCopy(
                            $expandedFolder,
                            $this->config['installationFolder']
                        );
                        $filesystem->recursiveRemoveDirectory($expandedFolder);
                    }

                    // Install sample data
                    $sampleDataSqlFile = glob($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'magento_sample_data*.sql');
                    $db = $this->config['db']; /* @var $db \PDO */
                    if (isset($sampleDataSqlFile[0])) {
                        $output->writeln('<comment>Importing ' . $sampleDataSqlFile[0] . '</comment>');
                        $db->exec(file_get_contents($sampleDataSqlFile[0]));
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
            '<question>Please enter the session save</question> <comment>[files]</comment>: ',
            'files'
        );

        $adminFrontname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin frontname</question> <comment>[admin]</comment> ',
            $this->notEmptyCallback,
            false,
            'admin'
        );

        $baseUrl = $dialog->askAndValidate(
            $output,
            '<question>Please enter the base url:</question> ',
            $this->notEmptyCallback,
            false
        );
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl

        $currency = $dialog->askAndValidate(
            $output,
            '<question>Please enter the default currency code </question> <comment>[' . $defaults['currency'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['currency']
        );

        $locale = $dialog->askAndValidate(
            $output,
            '<question>Please enter the lcoale code </question> <comment>[' . $defaults['locale'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['locale']
        );

        $timezone = $dialog->askAndValidate(
            $output,
            '<question>Please enter the timezone </question> <comment>[' . $defaults['timezone'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['timezone']
        );

        $adminUsername = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin username </question> <comment>[' . $defaults['admin_username'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_username']
        );

        $adminPassword = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin password </question> <comment>[' . $defaults['admin_password'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_password']
        );

        $adminFirstname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s firstname </question> <comment>[' . $defaults['admin_firstname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_firstname']
        );

        $adminLastname = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s lastname </question> <comment>[' . $defaults['admin_lastname'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_lastname']
        );

        $adminEmail = $dialog->askAndValidate(
            $output,
            '<question>Please enter the admin\'s email </question> <comment>[' . $defaults['admin_email'] . ']</comment>: ',
            $this->notEmptyCallback,
            false,
            $defaults['admin_email']
        );

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
        include($this->config['installationFolder'] . DIRECTORY_SEPARATOR . 'install.php');
        $this->replaceHtaccessFile($baseUrl);
        $output->writeln('<info>Successfully installed magento</info>');
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
}