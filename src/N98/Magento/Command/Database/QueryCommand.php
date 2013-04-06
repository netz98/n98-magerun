<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:query')
            ->addArgument('query', InputArgument::OPTIONAL, 'SQL query')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysql command. Do not execute')
            ->setDescription('Executes an SQL query on the database defined in local.xml')
        ;
    }
    
    /**
     * Returns the query string with escaped ' characters so it can be used
     * within the mysql -e argument.
     * 
     * The -e argument is enclosed by single quotes. As you can't escape
     * the single quote within the single quote, you have to end the quote,
     * then escape the single quote character and reopen the quote.
     * 
     * @param string $query
     * @return string
     */
    protected function getEscapedSql($query)
    {
        return str_replace("'", "'\''", $query);
    }
    
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);
        
        $query = $input->getArgument('query');
       
        if (($query = $input->getArgument('query')) === null) {
            $dialog = $this->getHelperSet()->get('dialog');
            $query = $dialog->ask($output, '<question>SQL query:</question>');
        }
        
        $query = $this->getEscapedSql($query);        
        
        $exec = 'mysql ' . $this->getMysqlClientToolConnectionString() . " -e '" . $query . "'";

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
        } else {
            exec($exec, $commandOutput, $returnValue);
            $output->writeln($commandOutput);
            if ($returnValue > 0) {
                $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
            }
        }        
    }
}
