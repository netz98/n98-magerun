<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Exception;
use Symfony\Component\Console\Command\Command;
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
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:user:delete';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Delete the account of a adminhtml user.';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                self::COMMAND_OPTION_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'Force'
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
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        } else {
            $output->writeln('<error>Aborting delete</error>');
        }

        return Command::SUCCESS;
    }
}
