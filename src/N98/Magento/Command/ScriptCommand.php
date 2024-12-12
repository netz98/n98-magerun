<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Exception;
use InvalidArgumentException;
use Mage;
use N98\Util\BinaryString;
use N98\Util\Exec;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Script command
 *
 * @package N98\Magento\Command
 */
class ScriptCommand extends AbstractMagentoCommand
{
    protected array $scriptVars = [];

    protected string $_scriptFilename = '';

    protected bool $_stopOnError = false;

    protected function configure(): void
    {
        $this
            ->setName('script')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Script file')
            ->addOption('define', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Defines a variable')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stops execution of script on error')
            ->setDescription('Runs multiple n98-magerun commands')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Example:

   # Set multiple config
   config:set "web/cookie/cookie_domain" example.com

   # Set with multiline values with "\n"
   config:set "general/store_information/address" "First line\nSecond line\nThird line"

   # This is a comment
   cache:flush


Optionally you can work with unix pipes.

   \$ echo "cache:flush" | n98-magerun-dev script

   \$ n98-magerun.phar script < filename

It is even possible to create executable scripts:

Create file `test.magerun` and make it executable (`chmod +x test.magerun`):

   #!/usr/bin/env n98-magerun.phar script

   config:set "web/cookie/cookie_domain" example.com
   cache:flush

   # Run a shell script with "!" as first char
   ! ls -l

   # Register your own variable (only key = value currently supported)
   \${my.var}=bar

   # Let magerun ask for variable value - add a question mark
   \${my.var}=?

   ! echo \${my.var}

   # Use resolved variables from n98-magerun in shell commands
   ! ls -l \${magento.root}/code/local

Pre-defined variables:

* \${magento.root}    -> Magento Root-Folder
* \${magento.version} -> Magento Version i.e. 1.7.0.2
* \${magento.edition} -> Magento Edition -> Community or Enterprise
* \${magerun.version} -> Magerun version i.e. 1.66.0
* \${php.version}     -> PHP Version
* \${script.file}     -> Current script file path
* \${script.dir}      -> Current script file dir

Variables can be passed to a script with "--define (-d)" option.

Example:

   $ n98-magerun.phar script -d foo=bar filename

   # This will register the variable \${foo} with value bar.

It's possible to define multiple values by passing more than one option.
HELP;
    }

    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->_scriptFilename = $input->getArgument('filename');
        $this->_stopOnError = $input->getOption('stop-on-error');
        $this->_initDefines($input);
        $script = $this->_getContent($this->_scriptFilename);
        $commands = explode("\n", $script);
        $this->initScriptVars();

        foreach ($commands as $command) {
            $command = trim($command);
            if ($command === '') {
                continue;
            }

            if ($command === '0') {
                continue;
            }

            $firstChar = substr($command, 0, 1);

            switch ($firstChar) {
                // comment
                case '#':
                    break;

                    // set var
                case '$':
                    $this->registerVariable($input, $output, $command);
                    break;

                    // run shell script
                case '!':
                    $this->runShellCommand($output, $command);
                    break;

                default:
                    $this->runMagerunCommand($input, $output, $command);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function _initDefines(InputInterface $input): void
    {
        $defines = $input->getOption('define');
        if (is_string($defines)) {
            $defines = [$defines];
        }

        if ((is_countable($defines) ? count($defines) : 0) > 0) {
            foreach ($defines as $define) {
                if (in_array(strstr($define, '='), ['', '0'], true) || strstr($define, '=') === false) {
                    throw new InvalidArgumentException('Invalid define');
                }

                $parts = BinaryString::trimExplodeEmpty('=', $define);
                $variable = $parts[0];
                $value = null;
                if (isset($parts[1])) {
                    $value = $parts[1];
                }

                $this->scriptVars['${' . $variable . '}'] = $value;
            }
        }
    }

    /**
     * @throws RuntimeException
     * @internal param string $input
     */
    protected function _getContent(string $filename): string
    {
        if ($filename === '-' || ($filename === '' || $filename === '0')) {
            // @phpstan-ignore argument.type
            $script = @\file_get_contents('php://stdin', 'r');
        } else {
            $script = @\file_get_contents($filename);
        }

        if ($script === '' || $script === '0' || $script === false) {
            throw new RuntimeException('Script file was not found');
        }

        return $script;
    }

    /**
     * @return void|mixed
     * @throws RuntimeException
     */
    protected function registerVariable(InputInterface $input, OutputInterface $output, string $commandString)
    {
        if (preg_match('/^(\$\{[a-zA-Z0-9-_.]+})=(.+)/', $commandString, $matches)) {
            if ($matches[2][0] === '?') {
                // Variable is already defined
                if (isset($this->scriptVars[$matches[1]])) {
                    return $this->scriptVars[$matches[1]];
                }

                $dialog = $this->getQuestionHelper();

                /**
                 * Check for select "?["
                 */
                if (isset($matches[2][1]) && $matches[2][1] === '[') {
                    if (preg_match('/\[(.+)]/', $matches[2], $choiceMatches)) {
                        $choices = BinaryString::trimExplodeEmpty(',', $choiceMatches[1]);
                        $question = new ChoiceQuestion(
                            '<info>Please enter a value for <comment>' . $matches[1] . '</comment>:</info> ',
                            $choices,
                        );
                        $selectedIndex = $dialog->ask($input, $output, $question);

                        $this->scriptVars[$matches[1]] = array_search($selectedIndex, $choices, true); # @todo check cmuench $choices[$selectedIndex]
                    } else {
                        throw new RuntimeException('Invalid choices');
                    }
                } else {
                    // normal input
                    $question = new Question('<info>Please enter a value for <comment>' . $matches[1] . '</comment>: </info>');
                    $question->setValidator(function ($value) {
                        if ($value == '') {
                            throw new RuntimeException('Please enter a value');
                        }

                        return $value;
                    });

                    $this->scriptVars[$matches[1]] = $dialog->ask($input, $output, $question);
                }
            } else {
                $this->scriptVars[$matches[1]] = $this->_replaceScriptVars($matches[2]);
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    protected function runMagerunCommand(InputInterface $input, OutputInterface $output, string $commandString): void
    {
        $this->getApplication()->setAutoExit(false);
        $commandString = $this->_replaceScriptVars($commandString);
        $input = new StringInput($commandString);
        $exitCode = $this->getApplication()->run($input, $output);
        if ($exitCode !== 0 && $this->_stopOnError) {
            $this->getApplication()->setAutoExit(true);
            throw new RuntimeException('Script stopped with errors', $exitCode);
        }
    }

    protected function _prepareShellCommand(string $commandString): string
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

        return $this->_replaceScriptVars($commandString);
    }

    protected function initScriptVars(): void
    {
        if (class_exists('\Mage')) {
            $this->scriptVars['${magento.root}']    = $this->getApplication()->getMagentoRootFolder();
            $this->scriptVars['${magento.version}'] = Mage::getVersion();
            $this->scriptVars['${magento.edition}'] = is_callable(['\Mage', 'getEdition'])
                ? Mage::getEdition() : 'Community';
        }

        $phpVersion = phpversion();
        $this->scriptVars['${php.version}']     = substr($phpVersion, 0, (int) strpos($phpVersion, '-'));
        $this->scriptVars['${magerun.version}'] = $this->getApplication()->getVersion();
        $this->scriptVars['${script.file}']     = $this->_scriptFilename;
        $this->scriptVars['${script.dir}']      = dirname($this->_scriptFilename);
    }

    /**
     * @internal param $returnValue
     */
    protected function runShellCommand(OutputInterface $output, string $commandString): void
    {
        $commandString = $this->_prepareShellCommand($commandString);
        $returnValue = shell_exec($commandString);
        if (!($returnValue === '' || $returnValue === '0' || $returnValue === false || $returnValue === null)) {
            $output->writeln($returnValue);
        }
    }

    protected function _replaceScriptVars(string $commandString): string
    {
        return str_replace(array_keys($this->scriptVars), $this->scriptVars, $commandString);
    }
}
