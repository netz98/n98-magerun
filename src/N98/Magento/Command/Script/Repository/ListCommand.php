<?php

namespace N98\Magento\Command\Script\Repository;

use Description;
use Location;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List scripts command
 *
 * @package N98\Magento\Command\Script\Repository
 */
class ListCommand extends AbstractRepositoryCommand
{
    protected function configure()
    {
        $this
            ->setName('script:repo:list')
            ->setDescription('Lists all scripts in repository')
            ->addFormatOption()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
You can organize your scripts in a repository.
Simply place a script in folder */usr/local/share/n98-magerun/scripts* or in your home dir
in folder *<HOME>/.n98-magerun/scripts*.

Scripts must have the file extension *.magerun*.

After that you can list all scripts with the *script:repo:list* command.
The first line of the script can contain a comment (line prefixed with #) which will be displayed as description.

   $ n98-magerun.phar script:repo:list
HELP;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getScripts();
        if (count($files) > 0) {
            $table = [];
            foreach ($files as $file) {
                $table[] = [substr($file['fileinfo']->getFilename(), 0, -strlen(self::MAGERUN_EXTENSION)), $file['location'], $file['description']];
            }
        } else {
            $table = [];
        }

        if ($input->getOption('format') === null && count($table) === 0) {
            $output->writeln('<info>no script file found</info>');
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Script', Location::class, Description::class])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
