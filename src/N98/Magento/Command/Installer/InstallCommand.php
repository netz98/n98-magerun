<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\SubCommand\SubCommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
    protected $commandConfig;

    /**
     * @var SubCommandFactory;
     */
    protected $subCommandFactory;

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
            ->addOption('installSampleData', null, InputOption::VALUE_OPTIONAL, 'Install sample data')
            ->addOption(
                'useDefaultConfigParams',
                null,
                InputOption::VALUE_OPTIONAL,
                'Use default installation parameters defined in the yaml file'
            )
            ->addOption('baseUrl', null, InputOption::VALUE_OPTIONAL, 'Installation base url')
            ->addOption(
                'replaceHtaccessFile',
                null,
                InputOption::VALUE_OPTIONAL,
                'Generate htaccess file (for non vhost environment)'
            )
            ->addOption(
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
                'If --forceUseDb passed, force to use given database if it already exists.'
            )
            ->addOption(
                'composer-use-same-php-binary',
                null,
                InputOption::VALUE_NONE,
                'If --composer-use-same-php-binary passed, will invoke composer with the same PHP binary'
            )
            ->setDescription('Install magento');
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
* Download Magento by a list of git repos and zip files (mageplus, 
  magelte, official community packages).
* Try to create database if it does not exist.
* Installs Magento sample data if available (since version 1.2.0).
* Starts Magento installer
* Sets rewrite base in .htaccess file

Example of an unattended Magento CE 2.0.0 installation:

   $ n98-magerun2.phar install --dbHost="localhost" --dbUser="mydbuser" \
     --dbPass="mysecret" --dbName="magentodb" --installSampleData=yes \
     --useDefaultConfigParams=yes \
     --magentoVersionByName="magento-ce-2.0.0" \
     --installationFolder="magento" --baseUrl="http://magento.localdomain/"

Additionally, with --noDownload option you can install Magento working 
copy already stored in --installationFolder on the given database.

See it in action: https://youtu.be/WU-CbJ86eQc
HELP;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return function_exists('exec');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->commandConfig = $this->getCommandConfig();
        $this->writeSection($output, 'Magento Installation');

        $subCommandFactory = $this->createSubCommandFactory(
            $input,
            $output,
            'N98\Magento\Command\Installer\SubCommand' // sub-command namespace
        );

        // @todo load commands from config
        $subCommandFactory->create('PreCheckPhp')->execute();
        $subCommandFactory->create('SelectMagentoVersion')->execute();
        $subCommandFactory->create('ChooseInstallationFolder')->execute();
        $subCommandFactory->create('InstallComposer')->execute();

        $subCommandFactory->create('DownloadMagento')->execute();
        if ($input->getOption('only-download')) {
            return 0;
        }

        $subCommandFactory->create('CreateDatabase')->execute();
        $subCommandFactory->create('RemoveEmptyFolders')->execute();
        $subCommandFactory->create('SetDirectoryPermissions')->execute();
        $subCommandFactory->create('InstallMagento')->execute();
        $subCommandFactory->create('RewriteHtaccessFile')->execute();
        $subCommandFactory->create('InstallSampleData')->execute();
        $subCommandFactory->create('PostInstallation')->execute();
        $output->writeln('<info>Successfully installed magento</info>');

        return 0;
    }
}
