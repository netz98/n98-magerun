<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use N98\Magento\Modules;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;
use function iterator_to_array;

/**
 * List modules command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class ListCommand extends AbstractMagentoCommand implements CommandFormatable
{
    public const COMMAND_OPTION_COODPOOL = 'codepool';

    public const COMMAND_OPTION_STATUS = 'status';

    public const COMMAND_OPTION_VENDOR = 'vendor';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'List all installed modules.';

    /**
     * @var string
     */
    protected static string $noResultMessage = 'No modules match the specified criteria.';

    protected function configure()
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_COODPOOL,
                null,
                InputOption::VALUE_OPTIONAL,
                'Show modules in a specific codepool'
            )
            ->addOption(
                self::COMMAND_OPTION_STATUS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Show modules with a specific status'
            )
            ->addOption(
                self::COMMAND_OPTION_VENDOR,
                null,
                InputOption::VALUE_OPTIONAL,
                'Show modules of a specified vendor'
            )
            ->setAliases(['sys:modules:list']); // deprecated

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Installed Modules';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['codePool', 'Name', 'Version', 'Status'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        $modules = $this->filterModules($input);
        $this->data = iterator_to_array($modules);

        return $this->data;
    }

    /**
     * @param InputInterface $input
     *
     * @return Modules
     */
    private function filterModules(InputInterface $input): Modules
    {
        $modules = new Modules();
        return $modules
            ->findInstalledModules()
            ->filterModules($input);
    }
}
