<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use Description;
use Location;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractRepositoryCommand implements AbstractMagentoCommandFormatInterface
{
    protected const NO_DATA_MESSAGE = 'No script file found';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'script:repo:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all scripts in repository.';

    public function getHelp()
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
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            foreach ($this->getScripts() as $file) {
                $this->data[] = [
                    'Script'            => substr(
                        $file['fileinfo']->getFilename(),
                        0,
                        -strlen(self::MAGERUN_EXTENSION)
                    ),
                    Location::class     => $file['location'],
                    Description::class  => $file['description']
                ];
            }
        }

        return $this->data;
    }

    /**
     * Skip initialisation
     *
     * @param bool $soft
     * @return true
     */
    public function initMagento(bool $soft = false)
    {
        return true;
    }
}
