<?php

namespace N98\Magento\Command\Installer;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Filesystem;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UninstallCommand
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\Installer
 */
class UninstallCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->addOption(
                'installationFolder',
                null,
                InputOption::VALUE_OPTIONAL,
                'Folder where Magento is currently installed'
            )
            ->setDescription(
                'Uninstall magento (drops database and empties current folder or folder set via installationFolder)'
            )
        ;

        $help = <<<HELP
**Please be careful: This removes all data from your installation.**
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->chooseInstallationFolder($input, $output);
        $this->detectMagento($output);
        $this->getApplication()->setAutoExit(false);
        /* @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');

        $shouldUninstall = $input->getOption('force');
        if (!$shouldUninstall) {
            $shouldUninstall = $dialog->askConfirmation(
                $output,
                '<question>Really uninstall ?</question> <comment>[n]</comment>: ',
                false
            );
        }

        if ($shouldUninstall) {
            $input = new StringInput('db:drop --force');
            $this->getApplication()->run($input, $output);
            $fileSystem = new Filesystem();
            $output->writeln('<info>Remove directory </info><comment>' . $this->_magentoRootFolder . '</comment>');
            try {
                $fileSystem->recursiveRemoveDirectory($this->_magentoRootFolder);
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
            $output->writeln('<info>Done</info>');
        }
    }
}
