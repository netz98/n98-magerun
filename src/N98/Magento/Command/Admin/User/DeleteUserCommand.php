<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

/**
 * Delete admin command
 *
 * @package N98\Magento\Command\Admin\User
 */
class DeleteUserCommand extends AbstractAdminUserCommand
{
    public const COMMAND_OPTION_FORCE = 'force';

    /**
     * @var string
     */
    protected static $defaultName = 'admin:user:delete';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Deletes the account of a adminhtml user.';

    /**
     * {@inheritdoc}
     */
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
                self::COMMAND_OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Force'
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dialog = $this->getQuestionHelper();

        $user = $this->getUserByIdOrEmail($input, $output);
        if (!$user->getId()) {
            $output->writeln('<error>User was not found</error>');

            return Command::INVALID;
        }

        $shouldRemove = $input->getOption(self::COMMAND_OPTION_FORCE);
        if (!$shouldRemove) {
            $shouldRemove = $dialog->ask(
                $input,
                $output,
                new ConfirmationQuestion('<question>Are you sure?</question> <comment>[n]</comment>: ', false),
            );
        }

        if ($shouldRemove) {
            try {
                $user->delete();
                $output->writeln('<info>User was successfully deleted</info>');
            } catch (Exception $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }
        } else {
            $output->writeln('<error>Aborting delete</error>');
        }

        return Command::SUCCESS;
    }
}
