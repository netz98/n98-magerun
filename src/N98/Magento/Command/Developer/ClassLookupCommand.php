<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class lookup command
 *
 * @package N98\Magento\Command\Developer
 */
class ClassLookupCommand extends AbstractMagentoCommand
{
    public const COMMAND_ARGUMENT_TYPE = 'type';

    public const COMMAND_ARGUMENT_NAME = 'name';


    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:class:lookup';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        /** @var string $type */
        $type = $input->getArgument(self::COMMAND_ARGUMENT_TYPE);
        /** @var string $name */
        $name = $input->getArgument(self::COMMAND_ARGUMENT_NAME);

        $resolved = $this->_getMageConfig()->getGroupedClassName($type, $name);

        $output->writeln(
            ucfirst($type) . ' <comment>' . $name . "</comment> " .
            "resolves to <comment>" . $resolved . '</comment>'
        );

        if (!class_exists('\\' . $resolved)) {
            $output->writeln('<info>Note:</info> Class <comment>' . $resolved . '</comment> does not exist!');
        }

        return Command::SUCCESS;
    }
}
