<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
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

    protected function configure()
    {
        $this
            ->setName('script')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Script file')
            ->setDescription('Runs multiple n98-magerun commands')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $script = $this->_getContent($input->getArgument('filename'));
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
    }

    /**
     * @param string $input
     * @return string
     */
    protected function _getContent($filename)
    {
        if ($filename == '-' || empty($filename)) {
            $script = \file_get_contents('php://stdin', 'r');
        } else {
            $script = \file_get_contents($filename);
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
                $this->scriptVars[$matches[1]] = $dialog->ask(
                    $output,
                    '<info>Please enter a value for <comment>' . $matches[1] . '</comment>:</info> '
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
        $commandString = $this->_replaceScriptVars($commandString);

        return $commandString;
    }

    protected function initScriptVars()
    {
        $this->scriptVars = array(
            '${magento.root}'    => $this->getApplication()->getMagentoRootFolder(),
            '${magento.version}' => \Mage::getVersion(),
            '${magento.edition}' => is_callable(array('\Mage', 'getEdition')) ? \Mage::getEdition() : 'Community',
            '${php.version}'     => substr(phpversion(), 0, strpos(phpversion(), '-')),
            '${magerun.version}' => $this->getApplication()->getVersion(),
        );
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
