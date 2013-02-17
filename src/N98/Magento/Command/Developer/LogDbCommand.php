<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Cache\ClearCommand as ClearCacheCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

class LogDbCommand extends AbstractMagentoCommand
{
    protected $_input = null;
    protected $_output = null;

    protected function configure()
    {
        $this->setName('dev:log:db')
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

        $this->_replaceVariable('$_debug');
        $this->_replaceVariable('$_logAllQueries');

        $output->writeln("<info>Done.  You can tail " . $this->_getDebugLogFilename() . "</info>");
    }

    /**
     * @return string
     * @todo I believe 1.5 and under put this in a different filename.
     */
    protected function _getDebugLogFilename()
    {
        return 'var/debug/pdo_mysql.log';
    }

    protected function  _replaceVariable($variable)
    {
        $varienAdapterPhpFile = $this->_getVarienAdapterPhpFile();
        $contents = file_get_contents($varienAdapterPhpFile);

        $debugLinePattern = "/protected\s" . '\\' . $variable . "\s*?=\s(false|true)/m";
        preg_match($debugLinePattern, $contents, $matches);
        if (!isset($matches[1])) {
            throw new \Exception("Problem finding the \$_debug parameter");
        }

        $currentValue = $matches[1];
        $newValue = ($currentValue == 'false') ? 'true' : 'false';
        $this->_output->writeln("<info>" . $variable . " is currently: " . $currentValue . "</info>");
        $this->_output->writeln("<info>Changing to : " . $newValue . "</info>");

        $contents = preg_replace($debugLinePattern, "protected " . $variable . " = " . $newValue, $contents);
        file_put_contents($varienAdapterPhpFile, $contents);
    }
}
