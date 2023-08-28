<?php

namespace N98\Magento\Command\LocalConfig;

use DateTime;
use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('local-config:generate')
            ->setDescription('Generates local.xml config')
            ->addArgument('db-host', InputArgument::OPTIONAL, 'Database host')
            ->addArgument('db-user', InputArgument::OPTIONAL, 'Database user')
            ->addArgument('db-pass', InputArgument::OPTIONAL, 'Database password')
            ->addArgument('db-name', InputArgument::OPTIONAL, 'Database name')
            ->addArgument('session-save', InputArgument::OPTIONAL, 'Session storage adapter')
            ->addArgument('admin-frontname', InputArgument::OPTIONAL, 'Admin front name')
            ->addArgument('encryption-key', InputArgument::OPTIONAL, 'Encryption Key')
        ;

        $help = <<<HELP
Generates the app/etc/local.xml.

- The file "app/etc/local.xml.template" (bundles with Magento) must exist!
- Currently the command does not validate anything you enter.
- The command will not overwrite existing app/etc/local.xml files.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $configFile = $this->_getLocalConfigFilename();
        $configFileTemplate = dirname($configFile) . '/local.xml.template';

        if (file_exists($configFile)) {
            $output->writeln(
                sprintf('<info>local.xml file already exists in folder "%s/app/etc"</info>', dirname($configFile))
            );
            return 0;
        }

        $this->writeSection($output, 'Generate Magento local.xml');
        $this->askForArguments($input, $output);
        if (!file_exists($configFileTemplate)) {
            $output->writeln(sprintf('<error>File %s does not exist.</error>', $configFileTemplate));
            return 0;
        }

        if (!is_writable(dirname($configFileTemplate))) {
            $output->writeln(sprintf('<error>Folder %s is not writeable</error>', dirname($configFileTemplate)));
            return 0;
        }

        $content = file_get_contents($configFileTemplate);
        $key = $input->getArgument('encryption-key') ?: md5(uniqid());

        $replace = [
            '{{date}}'               => $this->_wrapCData(date(DateTime::RFC2822)),
            '{{key}}'                => $this->_wrapCData($key),
            '{{db_prefix}}'          => $this->_wrapCData(''),
            '{{db_host}}'            => $this->_wrapCData($input->getArgument('db-host')),
            '{{db_user}}'            => $this->_wrapCData($input->getArgument('db-user')),
            '{{db_pass}}'            => $this->_wrapCData($input->getArgument('db-pass')),
            '{{db_name}}'            => $this->_wrapCData($input->getArgument('db-name')),
            // typo intended -> magento has a little typo bug "statemants".
            '{{db_init_statemants}}' => $this->_wrapCData('SET NAMES utf8'),
            '{{db_model}}'           => $this->_wrapCData('mysql4'),
            '{{db_type}}'            => $this->_wrapCData('pdo_mysql'),
            '{{db_pdo_type}}'        => $this->_wrapCData(''),
            '{{session_save}}'       => $this->_wrapCData($input->getArgument('session-save')),
            '{{admin_frontname}}'    => $this->_wrapCData($input->getArgument('admin-frontname')),
        ];

        $newFileContent = str_replace(array_keys($replace), array_values($replace), $content);
        if (false === file_put_contents($configFile, $newFileContent)) {
            $output->writeln('<error>could not save config</error>');
            return 0;
        }

        $output->writeln('<info>Generated config</info>');
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function askForArguments(InputInterface $input, OutputInterface $output)
    {
        /* @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');

        $messagePrefix = 'Please enter the ';
        $arguments = ['db-host'         => ['prompt' => 'database host', 'required' => true], 'db-user'         => ['prompt' => 'database username', 'required' => true], 'db-pass'         => ['prompt' => 'database password', 'required' => false], 'db-name'         => ['prompt' => 'database name', 'required' => true], 'session-save'    => ['prompt' => 'session save', 'required' => true, 'default' => 'files'], 'admin-frontname' => ['prompt' => 'admin frontname', 'required' => true, 'default' => 'admin']];

        foreach ($arguments as $argument => $options) {
            if (isset($options['default']) && $input->getArgument($argument) === null) {
                $input->setArgument(
                    $argument,
                    $dialog->ask(
                        $output,
                        sprintf('<question>%s%s:</question>', $messagePrefix, $options['prompt']),
                        (string) $options['default']
                    )
                );
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
    protected function _getLocalConfigFilename()
    {
        $configFile = $this->_magentoRootFolder . '/app/etc/local.xml';
        return $configFile;
    }

    /**
     * wrap utf-8 string as a <![CDATA[ ... ]]> section if the string has length.
     *
     * in case the string has length and not the whole string can be wrapped in a CDATA section (because it contains
     * a sequence that can not be part of a CDATA section "]]>") the part that can well be.
     *
     * @param string $string
     *
     * @return string CDATA section or equivalent
     */
    protected function _wrapCData($string)
    {
        $buffer = strtr($string, [']]>' => ']]>]]&gt;<![CDATA[']);
        $buffer = '<![CDATA[' . $buffer . ']]>';
        $buffer = strtr($buffer, ['<![CDATA[]]>' => '']);

        return $buffer;
    }
}
