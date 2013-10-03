<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\String;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $scriptVars = array();

    /**
     * @var string
     */
    protected $_scriptFilename = '';

    protected function configure()
    {
        $this
            ->setName('script')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Script file')
            ->addOption('define', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Defines a variable')
            ->setDescription('Runs multiple n98-magerun commands')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_scriptFilename = $input->getArgument('filename');
        $this->_initDefines($input);
        $script = $this->_getContent($this->_scriptFilename);
        $commands = explode("\n", $script);
        $this->initScriptVars();

        foreach ($commands as $commandString) {
            $commandString = trim($commandString);
            if (empty($commandString)) {
                continue;
            }
            $firstChar = substr($commandString, 0, 1);

            switch ($firstChar) {

                // comment
                case '#':
                    continue;
                    break;

                // set var
                case '$':
                    $this->registerVariable($output, $commandString);
                    break;

                // run shell script
                case '!':
                    $this->runShellCommand($output, $commandString);
                    break;

                default:
                    $this->runMagerunCommand($input, $output, $commandString);
            }
        }
    }

    /**
     * @param InputInterface $input
     */
    protected function _initDefines(InputInterface $input)
    {
        $defines = $input->getOption('define');
        if (is_string($defines)) {
            $defines = array($defines);
        }
        if (count($defines) > 0) {
            foreach ($defines as $define) {
                list($variable, $value) = String::trimExplodeEmpty('=', $define);
                $this->scriptVars['${' . $variable. '}'] = $value;
            }
        }
    }

    /**
     * @param string $input
     * @return string
     */
    protected function _getContent($filename)
    {
        if ($filename == '-' || empty($filename)) {
            $script = @\file_get_contents('php://stdin', 'r');
        } else {
            $script = @\file_get_contents($filename);
        }

        if (!$script) {
            throw new \RuntimeException('Script file was not found');
        }

        return $script;
    }

    /**
     * @param OutputInterface $output
     * @param string          $commandString
     * @return void
     */
    protected function registerVariable(OutputInterface $output, $commandString)
    {
        if (preg_match('/^(\$\{[a-zA-Z0-9-_.]+\})=(.+)/', $commandString, $matches)) {
            if ($matches[2] == '?') {
                $dialog = $this->getHelperSet()->get('dialog');
                /* @var $dialog DialogHelper */
                $this->scriptVars[$matches[1]] = $dialog->askAndValidate(
                    $output,
                    '<info>Please enter a value for <comment>' . $matches[1] . '</comment>:</info> ',
                    function($value) {
                        if ($value == '') {
                            throw new \Exception('Please enter a value');
                        }

                        return $value;
                    }
                );
            } else {
                $this->scriptVars[$matches[1]] = $matches[2];
            }
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param                 $commandString
     */
    protected function runMagerunCommand(InputInterface $input, OutputInterface $output, $commandString)
    {
        $this->getApplication()->setAutoExit(false);
        $commandString = $this->_replaceScriptVars($commandString);
        $input = new StringInput($commandString);
        $this->getApplication()->run($input, $output);
    }

    /**
     * @param $commandString
     */
    protected function _prepareShellCommand($commandString)
    {
        $commandString = ltrim($commandString, '!');

        // @TODO find a better place
        if (strstr($commandString, '${magento.root}')
            || strstr($commandString, '${magento.version}')
            || strstr($commandString, '${magento.edition}')
        ) {
            $this->initMagento();
        }
        $this->initScriptVars();
        $commandString = $this->_replaceScriptVars($commandString);

        return $commandString;
    }

    protected function initScriptVars()
    {
        if (class_exists('\Mage')) {
            $this->scriptVars['${magento.root}'] = $this->getApplication()->getMagentoRootFolder();
            $this->scriptVars['${magento.version}'] = \Mage::getVersion();
            $this->scriptVars['${magento.edition}'] = is_callable(array('\Mage', 'getEdition')) ? \Mage::getEdition() : 'Community';
        }

        $this->scriptVars['${php.version}']     = substr(phpversion(), 0, strpos(phpversion(), '-'));
        $this->scriptVars['${magerun.version}'] = $this->getApplication()->getVersion();
        $this->scriptVars['${script.file}'] = $this->_scriptFilename;
        $this->scriptVars['${script.dir}'] = dirname($this->_scriptFilename);
    }

    /**
     * @param OutputInterface $output
     * @param                 $commandString
     * @param                 $returnValue
     */
    protected function runShellCommand(OutputInterface $output, $commandString)
    {
        $commandString = $this->_prepareShellCommand($commandString);
        $returnValue = shell_exec($commandString);
        if (!empty($returnValue)) {
            $output->writeln($returnValue);
        }
    }

    /**
     * @param $commandString
     * @return mixed
     */
    protected function _replaceScriptVars($commandString)
    {
        $commandString = str_replace(array_keys($this->scriptVars), $this->scriptVars, $commandString);

        return $commandString;
    }
}
