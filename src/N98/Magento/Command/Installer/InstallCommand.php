<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Package\Loader\ArrayLoader as PackageLoader;
use Composer\Factory as ComposerFactory;
use Composer\IO\ConsoleIO;

class InstallCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install magento <comment>(experimental)</comment>')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, 'Magento Installation');
        $magentoVersion = $this->selectMagentoVersion($input, $output);
        $magentoInstallationFolder = $this->chooseInstalltionFolder($input, $output);
        $this->downloadMagento($input, $output, $magentoVersion, $magentoInstallationFolder);
        $this->installMagento($input, $output, $magentoInstallationFolder);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function selectMagentoVersion(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getCommandConfig();

        $question = array();
        foreach ($config['packages'] as $key => $package) {
            $question[] = '<comment>[' . ($key+1) . ']</comment> ' . $package['name'] . "\n";
        }
        $question[] = "<question>Choose a magento version:</question> ";

        $type = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($config) {
            if (!in_array($typeInput, range(1, count($config['packages'])))) {
                throw new \InvalidArgumentException('Invalid type');
            }

            return $typeInput;
        });

        return $config['packages'][$type - 1];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
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

        return $installationFolder;
    }

    /**
     * @param array $magentoVersionData
     * @param string $installationFolder
     * @return bool
     */
    public function downloadMagento(InputInterface $input, OutputInterface $output, array $magentoVersionData, $installationFolder) {
        try {
            if (file_exists($installationFolder . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
                $output->writeln('<error>A magento installation already exists in this folder </error>');
                return false;
            }

            $packageLoader = new PackageLoader();
            $package = $packageLoader->load($magentoVersionData);

            $io = new ConsoleIO($input, $output, $this->getHelperSet());
            $composer = ComposerFactory::create($io, array());
            $dm = $composer->getDownloadManager();
            $dm->download($package, $installationFolder, true);

        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }

        return true;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $magentoInstallationFolder
     * @return array
     */
    protected function installMagento(InputInterface $input, OutputInterface $output, $magentoInstallationFolder)
    {
        $notEmptyCallback = function($input) {
            if (empty($input)) {
                throw new \InvalidArgumentException('Please enter a value');
            }
            return $input;
        };

        // @TODO add default values...
        $dialog = $this->getHelperSet()->get('dialog');

        /**
         * Database
         */
        do {
            $dbHost = $dialog->askAndValidate($output, '<question>Please enter the database host</question> <comment>[localhost]</comment>: ', $notEmptyCallback, false, 'localhost');
            $dbUser = $dialog->askAndValidate($output, '<question>Please enter the database username:</question> ', $notEmptyCallback);
            $dbPass = $dialog->ask($output, '<question>Please enter the database password:</question> ');
            $dbName = $dialog->askAndValidate($output, '<question>Please enter the database name:</question> ', $notEmptyCallback);
        } while (!$this->validateDatabaseSettings($output, $dbHost, $dbUser, $dbPass, $dbName));

        /**
         * Admin
         */
        $sessionSave = $dialog->ask($output, '<question>Please enter the session save</question> <comment>[files]</comment>: ', 'files');
        $adminFrontname = $dialog->askAndValidate($output, '<question>Please enter the admin frontname</question> <comment>[admin]</comment> ', $notEmptyCallback, false, 'admin');
        $baseUrl = $dialog->askAndValidate($output, '<question>Please enter the base url:</question> ', $notEmptyCallback, false);
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl
        $defaultCurrency = $dialog->askAndValidate($output, '<question>Please enter the default currency code </question> <comment>[EUR]</comment>: ', $notEmptyCallback, false, 'EUR');

        $_SERVER['argv']['license_agreement_accepted'] = 'yes';
        $_SERVER['argv']['locale'] = 'de_DE';
        $_SERVER['argv']['timezone'] = 'Europe/Berlin';
        $_SERVER['argv']['db_host'] = $dbHost;
        $_SERVER['argv']['db_name'] = $dbName;
        $_SERVER['argv']['db_user'] = $dbUser;
        $_SERVER['argv']['db_pass'] = $dbPass;
        $_SERVER['argv']['url'] = $baseUrl;
        $_SERVER['argv']['use_rewrites'] = 'yes';
        $_SERVER['argv']['use_secure'] = 'no';
        $_SERVER['argv']['secure_base_url'] = '';
        $_SERVER['argv']['use_secure_admin'] = 'no';
        $_SERVER['argv']['admin_username'] = 'admin';
        $_SERVER['argv']['admin_lastname'] = 'Bimelhuber';
        $_SERVER['argv']['admin_firstname'] = 'Peter';
        $_SERVER['argv']['admin_email'] = 'peter.bimbelhuber@example.com';
        $_SERVER['argv']['admin_password'] = 'password123';
        $_SERVER['argv']['session_save'] = $sessionSave;
        $_SERVER['argv']['admin_frontname'] = $adminFrontname;
        $_SERVER['argv']['default_currency'] = $defaultCurrency;
        $_SERVER['argv']['skip_url_validation'] = 'yes';
        $this->replaceHtaccessFile($magentoInstallationFolder, $baseUrl);
        $output->writeln('<info>Start installation process.</info>');
        include($magentoInstallationFolder . DIRECTORY_SEPARATOR . 'install.php');
        $this->replaceHtaccessFile($magentoInstallationFolder, $baseUrl);
        $output->writeln('<info>Successfully installed magento</info>');
    }

    /**
     * @param OutputInterface $output
     * @param string $dbHost
     * @param string $dbUser
     * @param string $dbPass
     * @param string $dbName
     * @return bool
     */
    protected function validateDatabaseSettings(OutputInterface $output, $dbHost, $dbUser, $dbPass, $dbName)
    {
        try {
            $db = new \PDO('mysql:host='. $dbHost, $dbUser, $dbPass);
            if (!$db->query('USE ' . $dbName)) {
                $db->query("CREATE DATABASE `$dbName`");
                $output->writeln('<info>Created database ' . $dbName . '</info>');
            }

            return true;
        } catch (\PDOException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return false;
    }

    /**
     * @param string $installationFolder
     * @param string $baseUrl
     */
    protected function replaceHtaccessFile($installationFolder, $baseUrl)
    {
        $content = file_get_contents($installationFolder . DIRECTORY_SEPARATOR . '.htaccess');
        copy($installationFolder . DIRECTORY_SEPARATOR . '.htaccess', $installationFolder . DIRECTORY_SEPARATOR . '.htaccess.dist');
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($installationFolder . DIRECTORY_SEPARATOR . '.htaccess', $content);
    }
}