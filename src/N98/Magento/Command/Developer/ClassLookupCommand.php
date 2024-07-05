<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function class_exists;
use function ucfirst;

/**
 * Class lookup command
 *
 * @package N98\Magento\Command\Developer
 */
class ClassLookupCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_TYPE = 'type';

    public const COMMAND_ARGUMENT_NAME = 'name';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:class:lookup';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Resolves a grouped class name.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_TYPE,
                InputArgument::REQUIRED,
                'The type of the class (helper|block|model)'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_NAME,
                InputArgument::REQUIRED,
                'The grouped class name'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @uses Mage::getConfig()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $type */
        $type = $input->getArgument(self::COMMAND_ARGUMENT_TYPE);
        /** @var string $name */
        $name = $input->getArgument(self::COMMAND_ARGUMENT_NAME);

        $resolved = Mage::getConfig()->getGroupedClassName($type, $name);

        $output->writeln(sprintf(
            '%s <comment>%s</comment> resolves to <comment>%s</comment>',
            ucfirst($type),
            $name,
            $resolved
        ));

        if (!class_exists('\\' . $resolved)) {
            $output->writeln(sprintf('<info>Note:</info> Class <comment>%s</comment> does not exist!', $resolved));
        }

        return Command::SUCCESS;
    }
}
