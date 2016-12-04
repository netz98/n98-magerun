<?php

namespace N98\Magento\Command\Developer;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Developer\Console\Psy\Shell;
use N98\Util\Unicode\Charset;
use Psy\Configuration;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:console')
            ->setDescription(
                'Opens PHP interactive shell with initialized Mage::app() <comment>(Experimental)</comment>'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $initialized = false;
        try {
            $this->detectMagento($output);
            $initialized = $this->initMagento();
        } catch (Exception $e) {
            // do nothing
        }

        $consoleOutput = new ShellOutput();
        $config = new Configuration();
        $shell = new Shell($config);

        if ($initialized) {
            $ok = Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR);
            $edition = $this->getApplication()->isMagentoEnterprise() ? 'EE' : 'CE';
            $consoleOutput->writeln(
                '<fg=black;bg=green>Magento ' . \Mage::getVersion() . ' ' . $edition .
                ' initialized.</fg=black;bg=green> ' . $ok
            );
        } else {
            $consoleOutput->writeln('<fg=black;bg=yellow>Magento is not initialized.</fg=black;bg=yellow>');
        }

        $help = <<<'help'
At the prompt, type <comment>help</comment> for some help.

To exit the shell, type <comment>^D</comment>.
help;

        $consoleOutput->writeln($help);

        $shell->run($input, $consoleOutput);
    }
}
