<?php

namespace N98\Magento\Command\Installer\SubCommand;

use Exception;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class InstallMagento
 * @package N98\Magento\Command\Installer\SubCommand
 */
class InstallMagento extends AbstractSubCommand
{
    /**
     * @deprecated since since 1.3.1; Use constant from Exec-Utility instead
     * @see Exec::CODE_CLEAN_EXIT
     */
    const EXEC_STATUS_OK = 0;

    const MAGENTO_INSTALL_SCRIPT_PATH = 'install.php';

    /**
     * @var \Closure
     */
    protected $notEmptyCallback;

    /**
     * @return void
     * @throws Exception
     */
    public function execute()
    {
        $this->notEmptyCallback = function ($input) {
            if (empty($input)) {
                throw new \InvalidArgumentException('Please enter a value');
            }
            return $input;
        };

        $this->getCommand()->getApplication()->setAutoExit(false);

        /** @var $questionHelper QuestionHelper */
        $questionHelper = $this->getCommand()->getHelper('question');

        $defaults = $this->commandConfig['installation']['defaults'];

        $useDefaultConfigParams = $this->hasFlagOrOptionalBoolOption('useDefaultConfigParams');

        $question = new Question(
            sprintf(
                '<question>Please enter the session save:</question> <comment>[%s]</comment>: ',
                $defaults['session_save']
            ),
            $defaults['session_save']
        );

        $sessionSave = $useDefaultConfigParams ? $defaults['session_save'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the admin/backend frontname:</question> <comment>[%s]</comment> ',
                $defaults['backend-frontname']
            ),
            $defaults['backend-frontname']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminFrontname = $useDefaultConfigParams ? $defaults['backend-frontname'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the default currency code:</question> <comment>[%s]</comment>: ',
                $defaults['currency']
            ),
            $defaults['currency']
        );
        $question->setValidator($this->notEmptyCallback);
        $currency = $useDefaultConfigParams ? $defaults['currency'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the locale code:</question> <comment>[%s]</comment>: ',
                $defaults['locale']
            ),
            $defaults['locale']
        );
        $question->setValidator($this->notEmptyCallback);
        $locale = $useDefaultConfigParams ? $defaults['locale'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the timezone:</question> <comment>[%s]</comment>: ',
                $defaults['timezone']
            ),
            $defaults['timezone']
        );
        $question->setValidator($this->notEmptyCallback);
        $timezone = $useDefaultConfigParams ? $defaults['timezone'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the admin username:</question> <comment>[%s]</comment>: ',
                $defaults['admin_username']
            ),
            $defaults['admin_username']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminUsername = $useDefaultConfigParams ? $defaults['admin_username'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                '<question>Please enter the admin password:</question> <comment>[%s]</comment>: ',
                $defaults['admin_password']
            ),
            $defaults['admin_password']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminPassword = $useDefaultConfigParams ? $defaults['admin_password'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                "<question>Please enter the admin's firstname:</question> <comment>[%s]</comment>: ",
                $defaults['admin_firstname']
            ),
            $defaults['admin_firstname']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminFirstname = $useDefaultConfigParams ? $defaults['admin_firstname'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                "<question>Please enter the admin's lastname:</question> <comment>[%s]</comment>: ",
                $defaults['admin_lastname']
            ),
            $defaults['admin_lastname']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminLastname = $useDefaultConfigParams ? $defaults['admin_lastname'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $question = new Question(
            sprintf(
                "<question>Please enter the admin's email:</question> <comment>[%s]</comment>: ",
                $defaults['admin_email']
            ),
            $defaults['admin_email']
        );
        $question->setValidator($this->notEmptyCallback);
        $adminEmail = $useDefaultConfigParams ? $defaults['admin_email'] : $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );

        $validateBaseUrl = function ($url) {
            if (!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url)) {
                throw new \InvalidArgumentException('Please enter a valid URL');
            }

            if (parse_url($url, \PHP_URL_HOST) === 'localhost') {
                throw new \InvalidArgumentException(
                    'localhost cause problems! Please use 127.0.0.1 or another hostname'
                );
            }

            return $url;
        };

        $defaultBaseUrl = $this->commandConfig['installation']['base-url'];
        $question = new Question(
            sprintf(
                '<question>Please enter the base url:</question> <comment>[%s]</comment>:',
                $defaultBaseUrl
            ),
            $defaultBaseUrl
        );
        $question->setValidator($validateBaseUrl);
        $baseUrl = $this->input->getOption('baseUrl') ?? $questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );
        $baseUrl = rtrim($baseUrl, '/') . '/'; // normalize baseUrl

        /**
         * Correct session save (common mistake)
         */
        if ($sessionSave === 'file') {
            $sessionSave = 'files';
        }
        $this->_getDefaultSessionFolder($sessionSave);

        $argv = [
            'locale'                     => $locale,
            'timezone'                   => $timezone,
            'db_host'                    => $this->_prepareDbHost(),
            'db_name'                    => $this->config->getString('db_name'),
            'db_user'                    => $this->config->getString('db_user'),
            'url'                        => $baseUrl,
            'use_rewrites'               => 1,
            'use_secure'                 => 0,
            'use_secure_admin'           => 1,
            'admin_username'             => $adminUsername,
            'admin_lastname'             => $adminLastname,
            'admin_firstname'            => $adminFirstname,
            'admin_email'                => $adminEmail,
            'admin_password'             => $adminPassword,
            'session_save'               => $sessionSave,
            'backend_frontname'          => $adminFrontname,
            'default_currency'           => $currency,
            'license_agreement_accepted' => 'yes',
            'skip_url_validation'        => 'yes',
        ];

        $dbPass = $this->config->getString('db_pass');
        if (!empty($dbPass)) {
            $argv['db_pass'] = $dbPass;
        }

        if ($useDefaultConfigParams) {
            if (isset($defaults['encryption_key']) && $defaults['encryption_key'] != '') {
                $argv['encryption_key'] = $defaults['encryption_key'];
            }
            if ($defaults['use_secure'] != '') {
                $argv['use_secure'] = $defaults['use_secure'];
                $argv['secure_base_url'] = str_replace('http://', 'https://', $baseUrl);
            }
            if ($defaults['use_rewrites'] != '') {
                $argv['use_rewrites'] = $defaults['use_rewrites'];
            }
        }

        $this->config->setArray('installation_args', $argv);

        $this->runInstallScriptCommand($this->output, $this->config->getString('installationFolder'), $argv);
    }

    /**
     * @param $sessionSave
     */
    protected function _getDefaultSessionFolder($sessionSave)
    {
        /**
         * Try to create session folder
         */
        $defaultSessionFolder = $this->config->getString('installationFolder') . '/var/session';
        if ($sessionSave == 'files' && !is_dir($defaultSessionFolder)) {
            if (!mkdir($defaultSessionFolder) && !is_dir($defaultSessionFolder)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $defaultSessionFolder));
            }
        }
    }

    /**
     * @return string
     */
    protected function _prepareDbHost()
    {
        $dbHost = $this->config->getString('db_host');

        if ($this->config->getInt('db_port') !== 3306) {
            $dbHost .= ':' . (string)$this->config->getInt('db_port');

            return $dbHost;
        }

        return $dbHost;
    }

    /**
     * Invoke Magento PHP install script bin/magento setup:install
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
            if ($argValue === null) {
                $installArgs .= '--' . $argName . ' ';
            } elseif (is_bool($argValue)) {
                $installArgs .= '--' . $argName . ' ' . (int) $argValue . ' ';
            } else {
                $installArgs .= '--' . $argName . ' ' . escapeshellarg($argValue) . ' ';
            }
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
            $this->getCommand()->getApplication()->setAutoExit(true);
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
