<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\Developer\Console\Psy\Shell;
use N98\Util\Unicode\Charset;
use Psy\Configuration;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command
 *
 * @package N98\Magento\Command\Developer
 */
class ConsoleCommand extends AbstractCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:console';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Opens PHP interactive shell with initialized Mage::app() <comment>(Experimental)</comment>.';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $consoleOutput = new ShellOutput();
        $config = new Configuration();
        $shell = new Shell($config);

        $consoleOutput->writeln(sprintf(
            '<fg=black;bg=green>%s initialized.</fg=black;bg=green> %s',
            $this->getInstalledVersion(),
            Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR)
        ));

        $help = <<<HELP
At the prompt, type <comment>help</comment> for some help.

To exit the shell, type <comment>^D</comment>.
HELP;

        $consoleOutput->writeln($help);

        $shell->run($input, $consoleOutput);

        return Command::SUCCESS;
    }
}
