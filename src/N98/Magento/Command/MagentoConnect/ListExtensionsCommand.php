<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListExtensionsCommand extends AbstractConnectCommand
{
    protected function configure()
    {
        $this
            ->setName('extension:list')
            ->setAliases(array('extension:search'))
            ->addArgument('search', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Search string')
            ->setDescription('List magento connection extensions')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
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
                    }
                }
            }

            if (count($table) > 0) {
                $this->getHelper('table')
                    ->setHeaders(array('Package', 'Version', 'Stability'))
                    ->setRows($table)
                    ->render($output);
            }
        } else {
            $output->writeln('<error>' . $extensions . '</error>');
        }
    }
}