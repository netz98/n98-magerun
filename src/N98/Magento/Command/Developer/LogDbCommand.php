<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Cache\ClearCommand as ClearCacheCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class LogDbCommand extends AbstractMagentoCommand
{
    protected $_input = null;
    protected $_output = null;

    protected function configure()
    {
        $this->setName('dev:log:db')
             ->addOption('on', null, InputOption::VALUE_NONE, 'Force logging')
             ->addOption('off', null, InputOption::VALUE_NONE, 'Disable logging')
             ->setDescription('Turn on/off database query logging');
    }

    protected function  _getVarienAdapterPhpFile()
    {
        $varienAdapterPhpFile = $this->_magentoRootFolder . '/lib/Varien/Db/Adapter/Pdo/Mysql.php';
        return $varienAdapterPhpFile;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->detectMagento($output);
        $this->initMagento();

        $output->writeln("<info>Looking in " . $this->_getVarienAdapterPhpFile() . "</info>");

        $this->_replaceVariable($input, '$_debug');
        $this->_replaceVariable($input, '$_logAllQueries');

        $output->writeln("<info>Done. You can tail <comment>" . $this->_getDebugLogFilename() . "</comment></info>");
    }

    /**
     * @return string
     * @todo I believe 1.5 and under put this in a different filename.
     */
    protected function _getDebugLogFilename()
    {
        return 'var/debug/pdo_mysql.log';
    }

    protected function _replaceVariable($input, $variable)
    {
        $varienAdapterPhpFile = $this->_getVarienAdapterPhpFile();
        $contents = file_get_contents($varienAdapterPhpFile);

        $debugLinePattern = "/protected\s" . '\\' . $variable . "\s*?=\s(false|true)/m";
        preg_match($debugLinePattern, $contents, $matches);
        if (!isset($matches[1])) {
            throw new \Exception("Problem finding the \$_debug parameter");
        }

        $currentValue = $matches[1];
        if ($input->getOption('off')) {
            $newValue = 'false';
        } elseif ($input->getOption('on')) {
            $newValue = 'true';
        } else {
            $newValue = ($currentValue == 'false') ? 'true' : 'false';
        }

        $this->_output->writeln("<info>Changed <comment>" . $variable . "</comment> to <comment>" . $newValue  . "</comment></info>");

        $contents = preg_replace($debugLinePattern, "protected " . $variable . " = " . $newValue, $contents);
        file_put_contents($varienAdapterPhpFile, $contents);
    }
}