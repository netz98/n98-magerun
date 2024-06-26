<?php

declare(strict_types=1);

namespace N98\Magento\Command\LocalConfig;

use DateTimeInterface;
use InvalidArgumentException;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Generate local.xml command
 *
 * @package N98\Magento\Command\LocalConfig
 */
class GenerateCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_DB_HOST          = 'db-host';
    public const COMMAND_ARGUMENT_DB_NAME          = 'db-name';
    public const COMMAND_ARGUMENT_DB_PASS          = 'db-pass';
    public const COMMAND_ARGUMENT_DB_USER          = 'db-user';
    public const COMMAND_ARGUMENT_SESSION_SAVE     = 'session-save';
    public const COMMAND_ARGUMENT_ADMIN_FRONTNAME  = 'admin-frontname';
    public const COMMAND_ARGUMENT_ENCRYPTION_KEY   = 'encryption-key';

    /**
     * @var string
     */
    protected static $defaultName = 'local-config:generate';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Generates local.xml config.';

    protected static bool $initMagentoFlag = false;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    public function __construct()
    {
        parent:: __construct();
        $this->filesystem = new Filesystem();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_DB_HOST,
                InputArgument::OPTIONAL,
                'Database host'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_DB_USER,
                InputArgument::OPTIONAL,
                'Database user'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_DB_PASS,
                InputArgument::OPTIONAL,
                'Database password'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_DB_NAME,
                InputArgument::OPTIONAL,
                'Database name'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_SESSION_SAVE,
                InputArgument::OPTIONAL,
                'Session storage adapter'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ADMIN_FRONTNAME,
                InputArgument::OPTIONAL,
                'Admin front name'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ENCRYPTION_KEY,
                InputArgument::OPTIONAL,
                'Encryption Key'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Generates the app/etc/local.xml.

- The file "app/etc/local.xml.template" (bundles with Magento) must exist!
- Currently the command does not validate anything you enter.
- The command will not overwrite existing app/etc/local.xml files.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $this->_getLocalConfigFilename();
        if ($this->filesystem->exists($configFile)) {
            $output->writeln(sprintf(
                '<info>local.xml file already exists in folder "%s/app/etc"</info>',
                Path::getDirectory($configFile)
            ));
            return Command::FAILURE;
        }

        $this->writeSection($output, 'Generate local.xml');
        $this->askForArguments($input, $output);

        $configFileTemplate = Path::getDirectory($configFile) . '/local.xml.template';
        if (!$this->filesystem->exists($configFileTemplate)) {
            $output->writeln(sprintf('<error>File %s does not exist.</error>', $configFileTemplate));
            return Command::FAILURE;
        }

        try {
            $this->filesystem->touch($configFile);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }

        try {
            $content = $this->filesystem->readFile($configFileTemplate);
            if (!$content) {
                $output->writeln(sprintf(
                    '<error>File %s has no content</error>',
                    Path::getDirectory($configFileTemplate)
                ));
            }
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }

        /** @var string $key */
        $key = $input->getArgument(self::COMMAND_ARGUMENT_ENCRYPTION_KEY) ?: md5(uniqid());
        /** @var string $dbHost */
        $dbHost = $input->getArgument(self::COMMAND_ARGUMENT_DB_HOST);
        /** @var string $dbUser */
        $dbUser = $input->getArgument(self::COMMAND_ARGUMENT_DB_USER);
        /** @var string $dbPass */
        $dbPass = $input->getArgument(self::COMMAND_ARGUMENT_DB_PASS);
        /** @var string $dbName */
        $dbName = $input->getArgument(self::COMMAND_ARGUMENT_DB_NAME);
        /** @var string $session */
        $session = $input->getArgument(self::COMMAND_ARGUMENT_SESSION_SAVE);
        /** @var string $frontName */
        $frontName = $input->getArgument(self::COMMAND_ARGUMENT_ADMIN_FRONTNAME);

        $replace = [
            '{{date}}'               => $this->_wrapCData(date(DateTimeInterface::RFC2822)),
            '{{key}}'                => $this->_wrapCData($key),
            // Prefix does not work with sample data
            '{{db_prefix}}'          => $this->_wrapCData(''),
            '{{db_host}}'            => $this->_wrapCData($dbHost),
            '{{db_user}}'            => $this->_wrapCData($dbUser),
            '{{db_pass}}'            => $this->_wrapCData($dbPass),
            '{{db_name}}'            => $this->_wrapCData($dbName),
            // typo intended -> magento has a little typo bug "statements".
            '{{db_init_statemants}}' => $this->_wrapCData('SET NAMES utf8'),
            '{{db_model}}'           => $this->_wrapCData('mysql4'),
            '{{db_type}}'            => $this->_wrapCData('pdo_mysql'),
            '{{db_pdo_type}}'        => $this->_wrapCData(''),
            '{{session_save}}'       => $this->_wrapCData($session),
            '{{admin_frontname}}'    => $this->_wrapCData($frontName),
        ];

        $newFileContent = str_replace(array_keys($replace), array_values($replace), $content);

        try {
            $this->filesystem->dumpFile($configFile, $newFileContent);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }

        $output->writeln('<info>Generated config</info>');
        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function askForArguments(InputInterface $input, OutputInterface $output): void
    {
        $dialog = $this->getQuestionHelper();

        $messagePrefix = 'Please enter the ';
        $arguments = [
            self::COMMAND_ARGUMENT_DB_HOST => [
                'prompt' => 'database host',
                'required' => true
            ],
            self::COMMAND_ARGUMENT_DB_USER => [
                'prompt' => 'database username',
                'required' => true
            ],
            self::COMMAND_ARGUMENT_DB_PASS => [
                'prompt' => 'database password',
                'required' => false
            ],
            self::COMMAND_ARGUMENT_DB_NAME => [
                'prompt' => 'database name',
                'required' => true
            ],
            self::COMMAND_ARGUMENT_SESSION_SAVE => [
                'prompt' => 'session save',
                'required' => true,
                'default' => 'files'
            ],
            self::COMMAND_ARGUMENT_ADMIN_FRONTNAME => [
                'prompt' => 'admin frontname',
                'required' => true,
                'default' => 'admin'
            ]
        ];

        foreach ($arguments as $argument => $options) {
            if (isset($options['default']) && $input->getArgument($argument) === null) {
                $question = new Question(
                    sprintf('<question>%s%s:</question> ', $messagePrefix, $options['prompt']),
                    (string) $options['default']
                );
                $input->setArgument($argument, $dialog->ask($input, $output, $question));
            } else {
                $input->setArgument(
                    $argument,
                    $this->getOrAskForArgument($argument, $input, $output, $messagePrefix . $options['prompt'])
                );
            }

            if ($options['required'] && $input->getArgument($argument) === null) {
                throw new InvalidArgumentException(sprintf('%s was not set', $argument));
            }
        }
    }

    /**
     * @return string
     */
    protected function _getLocalConfigFilename(): string
    {
        return $this->_magentoRootFolder . '/app/etc/local.xml';
    }

    /**
     * wrap utf-8 string as a <![CDATA[ ... ]]> section if the string has length.
     *
     * in case the string has length and not the whole string can be wrapped in a CDATA section (because it contains
     * a sequence that can not be part of a CDATA section "]]>") the part that can well be.
     *
     * @param string $string
     * @return string CDATA section or equivalent
     */
    protected function _wrapCData(string $string): string
    {
        $buffer = strtr($string, [']]>' => ']]>]]&gt;<![CDATA[']);
        $buffer = '<![CDATA[' . $buffer . ']]>';
        $buffer = strtr($buffer, ['<![CDATA[]]>' => '']);

        return $buffer;
    }
}
