<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractRepositoryCommand
{
    protected function configure()
    {
        $this
            ->setName('script:repo:list')
            ->setDescription('Lists all scripts in repository')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->getScripts();
        if (count($files) > 0) {
            $table = array();
            foreach ($files as $file) {
                $table[] = array(
                    $file['fileinfo']->getFilename(),
                    $file['location'],
                    $file['description'],
                );
            }
            $this->getHelper('table')
                ->setHeaders(array('Script', 'Location', 'Description'))
                ->setRows($table)
                ->render($output);
        } else {
            $output->writeln('<info>no script file found</info>');
        }
    }
}