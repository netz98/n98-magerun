<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class VariablesCommand extends AbstractDatabaseCommand
{
    /**
     * http://www.digiwig.com/blog/how-to-speed-up-magento <- variables
     * http://aadant.com/blog/2013/09/30/poor-mans-online-optimize-in-5-6/
     */

    protected function configure()
    {
        $this
            ->setName('db:variables')
            ->addArgument('vars', InputArgument::OPTIONAL, 'Only output variables of specified name. Wildcards supported')
            ->setDescription('Dumps database for important variables')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );

        $help = <<<HELP
This command is useful to print all important variables about the current database.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $this->detectDbSettings($output);

//        $settings = array();
//        foreach ($this->dbSettings as $key => $value) {
//            $settings[$key] = (string)$value;
//        }
//
//        $rows = array();
//        foreach ($settings as $settingName => $settingValue) {
//            $rows[] = array($settingName, $settingValue);
//        }
//
//        $this->getHelper('table')
//            ->setHeaders(array('Name', 'Value'))
//            ->renderByFormat($output, $rows, $input->getOption('format'));
    }
}
