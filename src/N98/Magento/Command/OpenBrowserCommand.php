<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\OperatingSystem;

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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $os = new OperatingSystem();

        $opener = '';
        if ($os->isMacOs()) {
            $opener = 'open';
        } elseif ($os->isWindows()) {
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
            throw new \RuntimeException('No opener command like xde-open, gnome-open, kde-open was found.');
        }

        $this->detectMagento($output);
        if ($this->initMagento($output, $output)) {
            $store = $this->getHelperSet()->get('parameter')->askStore($input, $output);
            $url = $store->getBaseUrl(\Mage_Core_Model_Store::URL_TYPE_LINK) . '?___store=' . $store->getCode();
            $output->writeln('Opening URL <comment>' . $url . '</comment> in browser');
            exec(escapeshellcmd($opener . ' ' . $url));
        }
    }

}