<?php

namespace N98\Magento\Command;

use Mage;
use Mage_Core_Model_App;
use Mage_Core_Model_Store;
use N98\Util\Console\Helper\ParameterHelper;
use N98\Util\Exec;
use N98\Util\OperatingSystem;
use RuntimeException;
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
    protected function configure()
    {
        $this
            ->setName('open-browser')
            ->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID')
            ->setDescription('Open current project in browser <comment>(experimental)</comment>')
        ;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Exec::allowed();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        /** @var ParameterHelper $parameterHelper */
        $parameterHelper = $this->getHelper('parameter');

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
        return 0;
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
