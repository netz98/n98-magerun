<?php

namespace N98\Magento\Command\Developer\Log;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbCommand extends AbstractLogCommand
{
    protected function configure()
    {
        $this->setName('dev:log:db')
             ->addOption('on', null, InputOption::VALUE_NONE, 'Force logging. Additionally run \'git assume unchanged\'
                 ,in case if git repository detected')
             ->addOption('off', null, InputOption::VALUE_NONE, 'Disable logging')
             ->addOption('ignore-git', null, InputOption::VALUE_NONE, 'Do not run \'git assume unchanged\'')
             ->setDescription('Turn on/off database query logging');
    }

    /**
     * @return string
     */
    protected function  _getVarienAdapterPhpFile()
    {
        return $this->_magentoRootFolder . '/lib/Varien/Db/Adapter/Pdo/Mysql.php';
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $this->initMagento();

        $output->writeln("<info>Looking in " . $this->_getVarienAdapterPhpFile() . "</info>");

        $this->_replaceVariable($input, $output, '$_debug');
        $this->_replaceVariable($input, $output, '$_logAllQueries');

        if (!$input->getOption('ignore-git')) {
            $this->assumeUnchanged($input, $output, $this->_getVarienAdapterPhpFile());
        }

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

    /**
     * @param \Symfony\Component\Console\Input\InputInterface  $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                         $variable
     * @return void
     * @throws \Exception
     */
    protected function _replaceVariable($input, $output, $variable)
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

        $output->writeln("<info>Changed <comment>" . $variable . "</comment> to <comment>" . $newValue  . "</comment></info>");

        $contents = preg_replace($debugLinePattern, "protected " . $variable . " = " . $newValue, $contents);
        file_put_contents($varienAdapterPhpFile, $contents);

    }

    /**
     * @retun boolean
     */
    protected function _isGitDetected()
    {
        $gitPath = $this->_magentoRootFolder.'/.git';
        if (file_exists($gitPath) && is_dir($gitPath)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface  $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $path
     * @return void
     */
    protected function assumeUnchanged($input, $output, $path)
    {
        $path = preg_replace('/^' . preg_quote($this->_magentoRootFolder.'/','/') . '/', '', $path);
        if ($this->_isGitDetected()) {
            $cmd = 'git update-index --assume-unchanged '.$path;
            exec($cmd);
            $output->writeln("<info>".$cmd."</info>");
        }
    }
}