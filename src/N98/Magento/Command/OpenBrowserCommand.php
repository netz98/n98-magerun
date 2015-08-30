<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\OperatingSystem;
use N98\Util\Exec;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $opener = '';
        if (OperatingSystem::isMacOs()) {
            $opener = 'open';
        } elseif (OperatingSystem::isWindows()) {
            $opener = 'start';
        } else {
            // Linux
            if (exec('which xde-open')) {
                $opener = 'xdg-open';
            } elseif (exec('which gnome-open')) {
                $opener = 'gnome-open';
            } elseif (exec('which kde-open')) {
                $opener = 'kde-open';
            }
        }

        if (empty($opener)) {
            throw new RuntimeException('No opener command like xde-open, gnome-open, kde-open was found.');
        }

        $this->detectMagento($output);
        if ($this->initMagento($output)) {
            $store = $this->getHelperSet()->get('parameter')->askStore($input, $output, 'store', true);
            if ($store->getId() == \Mage_Core_Model_App::ADMIN_STORE_ID) {
                $adminFrontName = (string) \Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
                $url = rtrim($store->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_WEB), '/') . '/' . $adminFrontName;
            } else {
                $url = $store->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_LINK) . '?___store=' . $store->getCode();
            }
            $output->writeln('Opening URL <comment>' . $url . '</comment> in browser');
            Exec::run(escapeshellcmd($opener . ' ' . $url));
        }
    }

}
