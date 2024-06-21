<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Mage_Core_Exception;
use Mage_Core_Model_App;
use Mage_Core_Model_Store;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Open browser command
 *
 * @package N98\Magento\Command
 */
class OpenBrowserCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_STORE = 'store';
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'open-browser';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Open current project in browser <comment>(experimental)</comment>.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_STORE,
                InputArgument::OPTIONAL,
                'Store code or ID'
            )
        ;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $parameterHelper = $this->getParameterHelper();

        /** @var Mage_Core_Model_Store $store */
        $store = $parameterHelper->askStore($input, $output, self::COMMAND_ARGUMENT_STORE, true);
        if ($store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID) {
            $adminFrontName = (string) $this->_getMageConfig()->getNode('admin/routers/adminhtml/args/frontName');
            $url = rtrim($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), '/') . '/' . $adminFrontName;
        } else {
            $url = $store->getBaseUrl() . '?___store=' . $store->getCode();
        }
        $output->writeln(sprintf('Opening URL <comment>%s</comment> in browser', $url));

        $opener = $this->resolveOpenerCommand($output);
        Exec::run(escapeshellcmd($opener . ' ' . $url));

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @return string
     */
    private function resolveOpenerCommand(OutputInterface $output)
    {
        $opener = '';
        if (OperatingSystem::isMacOs()) {
            $opener = 'open';
        } elseif (OperatingSystem::isWindows()) {
            $opener = 'start';
        } else {
            // Linux
            if (exec('which xdg-open')) {
                $opener = 'xdg-open';
            } elseif (exec('which gnome-open')) {
                $opener = 'gnome-open';
            } elseif (exec('which kde-open')) {
                $opener = 'kde-open';
            }
        }

        if (empty($opener)) {
            throw new RuntimeException('No opener command like xdg-open, gnome-open, kde-open was found.');
        }

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $message = sprintf('open command is "%s"', $opener);
            $output->writeln(
                '<debug>' . $message . '</debug>'
            );
        }

        return $opener;
    }
}
