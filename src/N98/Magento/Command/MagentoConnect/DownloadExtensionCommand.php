<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadExtensionCommand extends AbstractConnectCommand
{
    protected function configure()
    {
        $this
            ->setName('extension:download')
            ->addArgument('package', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Packge to download')
            ->setDescription('Download magento-connect package')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $package
     */
    protected function doAction($input, $output, $package)
    {
        $output->writeln($this->callMageScript($input, $output, 'download community ' . $package));
    }
}