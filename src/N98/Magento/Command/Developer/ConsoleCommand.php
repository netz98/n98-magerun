<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $descriptorSpec = array(
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        );

        $prependFile = __DIR__ . '/../../../../../res/dev/console_auto_prepend.php';
        if ($this->getApplication()->isPharMode()) {
            $pharFile = $_SERVER['argv'][0];
            $prependFile = 'phar://' . $pharFile . '/res/dev/console_auto_prepend.php';
        }

        $exec = '/usr/bin/env php -d auto_prepend_file=' . escapeshellarg($prependFile) . ' -a';

        $pipes = array();
        $process = proc_open($exec, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot init interactive shell');
        }
    }
}