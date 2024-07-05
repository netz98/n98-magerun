<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List scripts command
 *
 * @package N98\Magento\Command\Script\Repository
 */
class ListCommand extends AbstractRepositoryCommand implements CommandDataInterface
{
    protected const NO_DATA_MESSAGE = 'No script file found';

    /**
     * @var string
     */
    protected static $defaultName = 'script:repo:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all scripts in repository.';

    protected static bool $initMagentoFlag = false;

    protected static bool $detectMagentoFlag = false;

    /**
     * @return string
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
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Script', 'Location', 'Description'];
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];
            foreach ($this->getScripts() as $file) {
                $this->data[] = [
                    substr(
                        $file['fileinfo']->getFilename(),
                        0,
                        -strlen(self::MAGERUN_EXTENSION)
                    ),
                    $file['location'],
                    $file['description']
                ];
            }
        }

        return $this->data;
    }
}
