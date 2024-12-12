<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Mage;
use Mage_Core_Model_App;
use Mage_Core_Model_Store;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Open browser command
 *
 * @package N98\Magento\Command
 */
class OpenBrowserCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('open-browser')
            ->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID')
            ->setDescription('Open current project in browser <comment>(experimental)</comment>')
        ;
    }

    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $parameterHelper = $this->getParameterHelper();

        $store = $parameterHelper->askStore($input, $output, 'store', true);
        if ($store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID) {
            $adminFrontName = (string) Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
            $url = rtrim($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), '/') . '/' . $adminFrontName;
        } else {
            $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . '?___store=' . $store->getCode();
        }

        $output->writeln('Opening URL <comment>' . $url . '</comment> in browser');

        $opener = $this->resolveOpenerCommand($output);
        Exec::run(escapeshellcmd($opener . ' ' . $url));

        return Command::SUCCESS;
    }

    private function resolveOpenerCommand(OutputInterface $output): string
    {
        $opener = '';
        if (OperatingSystem::isMacOs()) {
            $opener = 'open';
        } elseif (OperatingSystem::isWindows()) {
            $opener = 'start';
        } elseif (exec('which xdg-open')) {
            // Linux
            $opener = 'xdg-open';
        } elseif (exec('which gnome-open')) {
            $opener = 'gnome-open';
        } elseif (exec('which kde-open')) {
            $opener = 'kde-open';
        }

        if ($opener === '') {
            throw new RuntimeException('No opener command like xdg-open, gnome-open, kde-open was found.');
        }

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $message = sprintf('open command is "%s"', $opener);
            $output->writeln(
                '<debug>' . $message . '</debug>',
            );
        }

        return $opener;
    }
}
