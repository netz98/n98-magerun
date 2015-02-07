<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use Psy\Command\ListCommand;
use Psy\Configuration;
use N98\Magento\Command\Developer\Console\Psy\Shell;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:console')
            ->setDescription('Opens PHP interactive shell with initialized Mage::app() <comment>(Experimental)</comment>')
        ;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if (OperatingSystem::isWindows()) {

            return false;
        }

        if ($this->getApplication()->isPharMode()) {
            $pharFile = $_SERVER['argv'][0];
            return substr($pharFile, -5) == '.phar';
        }

        return true;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $this->initMagento();

        $consoleOutput = new ShellOutput();
        $config = new Configuration();
        $shell = new Shell($config);
        $shell->run($input, $consoleOutput);
    }
}