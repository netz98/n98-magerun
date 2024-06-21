<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * Change admin password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ChangePasswordCommand extends AbstractAdminUserCommand
{
    public const COMMAND_ARGUMENT_PASSWORD = 'password';

    public const COMMAND_ARGUMENT_USERNAME = 'username';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:user:change-password';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Changes the password of a adminhtml user.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_USERNAME,
                InputArgument::OPTIONAL,
                'Username'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_PASSWORD,
                InputArgument::OPTIONAL,
                'Password'
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

        /** @var string|null $username */
        $username = $input->getArgument(self::COMMAND_ARGUMENT_USERNAME);
        if ($username === null) {
            /** @var string $username */
            $username = $dialog->ask($input, $output, new Question('<question>Username:</question> '));
        }

        $user = $this->getUserModel()->loadByUsername($username);
        if ($user->getId() <= 0) {
            $output->writeln('<error>User was not found</error>');

            return Command::SUCCESS;
        }

        // Password
        if (($password = $input->getArgument(self::COMMAND_ARGUMENT_PASSWORD)) == null) {
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $dialog->ask($input, $output, $question);
        }

        try {
            $result = $user->validate();
            if (is_array($result)) {
                throw new RuntimeException(implode(PHP_EOL, $result));
            }
            $user->setPassword($password);
            $user->save();
            $output->writeln('<info>Password successfully changed</info>');
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}
