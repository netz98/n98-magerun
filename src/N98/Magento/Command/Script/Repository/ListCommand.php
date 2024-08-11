<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List scripts command
 *
 * @package N98\Magento\Command\Script\Repository
 */
class ListCommand extends AbstractRepositoryCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'script:repo:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all scripts in repository.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Script List';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['Script', 'Location', 'Description'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $table = [];
        $files = $this->getScripts();
        if (count($files) > 0) {
            foreach ($files as $file) {
                $table[] = [
                    substr($file['fileinfo']->getFilename(), 0, -strlen(self::MAGERUN_EXTENSION)),
                    $file['location'],
                    $file['description']
                ];
            }
        }

        return $table;
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
}
