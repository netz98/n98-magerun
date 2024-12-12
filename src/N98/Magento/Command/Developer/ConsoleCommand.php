<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Exception;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Developer\Console\Psy\Shell;
use N98\Util\Unicode\Charset;
use Psy\Configuration;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command
 *
 * @package N98\Magento\Command\Developer
 */
class ConsoleCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:console')
            ->setDescription(
                'Opens PHP interactive shell with initialized Mage::app() <comment>(Experimental)</comment>',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $initialized = false;
        try {
            $this->detectMagento($output);
            $initialized = $this->initMagento();
        } catch (Exception $exception) {
            // do nothing
        }

        $shellOutput = new ShellOutput();
        $configuration = new Configuration();
        $shell = new Shell($configuration);

        if ($initialized) {
            $ok = Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR);
            $edition = $this->getApplication()->isMagentoEnterprise() ? 'EE' : 'CE';
            $shellOutput->writeln(
                '<fg=black;bg=green>Magento ' . Mage::getVersion() . ' ' . $edition .
                ' initialized.</fg=black;bg=green> ' . $ok,
            );
        } else {
            $shellOutput->writeln('<fg=black;bg=yellow>Magento is not initialized.</fg=black;bg=yellow>');
        }

        $help = <<<'help_WRAP'
At the prompt, type <comment>help</comment> for some help.

To exit the shell, type <comment>^D</comment>.
help_WRAP;

        $shellOutput->writeln($help);
        $shell->run($input, $shellOutput);

        return Command::SUCCESS;
    }
}
