<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeExtensionCommand extends AbstractConnectCommand
{
    protected function configure()
    {
        $this
            ->setName('extension:upgrade')
            ->addArgument('package', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Package to upgrade')
            ->setDescription('Upgrade magento-connect package')
        ;

        $help = <<<HELP
* Requires Magento's `mage` shell script.
* Does not work with Windows as operating system.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $package
     */
    protected function doAction(InputInterface $input, OutputInterface $output, $package)
    {
        $output->writeln($this->callMageScript($input, $output, 'upgrade community ' . $package));
    }
}
