<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Uninstall command
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\Installer
 */
class UninstallCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('uninstall')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->addOption(
                'installationFolder',
                null,
                InputOption::VALUE_OPTIONAL,
                'Folder where Magento is currently installed',
            )
            ->setDescription(
                'Uninstall magento (drops database and empties current folder or folder set via installationFolder)',
            )
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
**Please be careful: This removes all data from your installation.**
HELP;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->chooseInstallationFolder($input, $output);
        $this->detectMagento($output);
        $this->getApplication()->setAutoExit(false);

        $questionHelper = $this->getQuestionHelper();

        $shouldUninstall = $input->getOption('force');
        if (!$shouldUninstall) {
            $confirmationQuestion = new ConfirmationQuestion(
                '<question>Really uninstall ?</question> <comment>[n]</comment>: ',
                false,
            );
            $shouldUninstall = $questionHelper->ask($input, $output, $confirmationQuestion);
        }

        if ($shouldUninstall) {
            $input = new StringInput('db:drop --force');
            $this->getApplication()->run($input, $output);
            $fileSystem = new Filesystem();
            $output->writeln('<info>Remove directory </info><comment>' . $this->_magentoRootFolder . '</comment>');
            try {
                $fileSystem->recursiveRemoveDirectory($this->_magentoRootFolder);
            } catch (Exception $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');
            }

            $output->writeln('<info>Done</info>');
        }

        return Command::SUCCESS;
    }
}
