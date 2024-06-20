<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Change admin status command
 *
 * @package N98\Magento\Command\Admin\User
 * @TODO(sr) toggle
 */
class ChangeStatusCommand extends AbstractAdminUserCommand
{
    public const COMMAND_OPTION_ACTIVATE = 'activate';

    public const COMMAND_OPTION_DEACTIVATE = 'deactivate';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:user:change-status';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Set active status of an adminhtml user. If no option is set the status will be toggled.';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ID,
                InputArgument::OPTIONAL,
                'Username or Email'
            )
            ->addOption(
                self::COMMAND_OPTION_ACTIVATE,
                null,
                InputOption::VALUE_NONE,
                'Activate user'
            )
            ->addOption(
                self::COMMAND_OPTION_DEACTIVATE,
                null,
                InputOption::VALUE_NONE,
                'Deactivate user'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $user = $this->getUserByIdOrEmail($input, $output);
        if (!$user->getId()) {
            $output->writeln('<error>User was not found</error>');

            return Command::INVALID;
        }

        try {
            $result = $user->validate();

            if (is_array($result)) {
                throw new RuntimeException(implode(PHP_EOL, $result));
            }

            if ($input->getOption(self::COMMAND_OPTION_ACTIVATE)) {
                $user->setIsActive(1);
            }

            if ($input->getOption(self::COMMAND_OPTION_DEACTIVATE)) {
                $user->setIsActive(0);
            }

            // toggle is_active
            if (!$input->getOption(self::COMMAND_OPTION_ACTIVATE)
                && !$input->getOption(self::COMMAND_OPTION_DEACTIVATE)
            ) {
                $user->setIsActive((int)!$user->getIsActive());
            }

            $user->save();

            if ($user->getIsActive() == 1) {
                $output->writeln(sprintf(
                    '<info>User <comment>%s</comment> is now <comment>active</comment></info>',
                    $user->getUsername()
                ));
            } else {
                $output->writeln(sprintf(
                    '<info>User <comment>%s</comment> is now <comment>inactive</comment></info>',
                    $user->getUsername()
                ));
            }
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
