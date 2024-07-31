<?php

namespace N98\Magento\Command\Admin\User;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Change admin user password command
 *
 * @package N98\Magento\Command\Admin\User
 */
class ChangePasswordCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:change-password')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->setDescription('Changes the password of a adminhtml user.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $dialog = $this->getQuestionHelper();

        // Username
        if (($username = $input->getArgument('username')) == null) {
            $username = $dialog->ask($input, $output, new Question('<question>Username:</question> '));
        }

        $user = $this->getUserModel()->loadByUsername($username);
        if ($user->getId() <= 0) {
            $output->writeln('<error>User was not found</error>');

            return 0;
        }

        // Password
        if (($password = $input->getArgument('password')) == null) {
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
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return 0;
    }
}
