<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandFormatInterface;
use N98\Magento\Modules;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List module(s) command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class ListCommand extends AbstractCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Installed Modules';

    public const COMMAND_OPTION_COODPOOL = 'codepool';

    public const COMMAND_OPTION_STATUS = 'status';

    public const COMMAND_OPTION_VENDOR = 'vendor';

    protected const NO_DATA_MESSAGE = 'No modules match the specified criteria.';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:module:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'List all installed modules.';

    protected function configure(): void
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
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        return iterator_to_array($this->filterModules($input));
    }

    /**
     * @param InputInterface $input
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
