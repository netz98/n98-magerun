<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListExtensionsCommand extends AbstractConnectCommand
{
    protected function configure()
    {
        $this
            ->setName('extension:list')
            ->setAliases(array('extension:search'))
            ->addArgument('search', InputArgument::OPTIONAL, 'Search string')
            ->setDescription('List magento connection extensions')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
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
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensions = $this->callMageScript($input, $output, 'list-available');
        if (!strstr($extensions, 'Please initialize Magento Connect installer')) {
            $searchString = $input->getArgument('search');
            $table = array();
            foreach (preg_split('/' . PHP_EOL . '/', $extensions) as $line) {
                if (strpos($line, ':') > 0) {
                    $matches = null;
                    if ($matches = $this->matchConnectLine($line)) {
                        if (!empty($searchString) && !stristr($line, $searchString)) {
                            continue;
                        }
                        $table[] = array(
                            $matches[1],
                            $matches[2],
                            $matches[3],
                        );
                        if (isset($matches[4]) && isset($matches[5])) {
                            $table[] = array(
                                $matches[1],
                                $matches[4],
                                $matches[5],
                            );
                        }
                        if (isset($matches[6]) && isset($matches[7])) {
                            $table[] = array(
                                $matches[1],
                                $matches[6],
                                $matches[7],
                            );
                        }
                    }
                }
            }

            if (count($table) > 0) {
                /* @var $tableHelper TableHelper */
                $tableHelper = $this->getHelper('table');
                $tableHelper
                    ->setHeaders(array('Package', 'Version', 'Stability'))
                    ->renderByFormat($output, $table, $input->getOption('format'));
            }
        } else {
            $output->writeln('<error>' . $extensions . '</error>');
        }
    }
}
