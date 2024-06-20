<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Mage_Core_Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;

/**
 * Create admin command
 *
 * @package N98\Magento\Command\Admin\User
 */
class CreateUserCommand extends AbstractAdminUserCommand
{
    public const COMMAND_ARGUMENT_USERNAME = 'username';

    public const COMMAND_ARGUMENT_EMAIL = 'email';

    public const COMMAND_ARGUMENT_PASSWORD = 'password';

    public const COMMAND_ARGUMENT_FIRSTNAME = 'firstname';

    public const COMMAND_ARGUMENT_LASTNAME = 'lastname';

    public const COMMAND_ARGUMENT_ROLE = 'role';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'admin:user:create';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Create admin user.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_USERNAME,
                InputArgument::OPTIONAL,
                'Username'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_EMAIL,
                InputArgument::OPTIONAL,
                'Email, empty string = generate'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_PASSWORD,
                InputArgument::OPTIONAL,
                'Password'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_FIRSTNAME,
                InputArgument::OPTIONAL,
                'Firstname'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_LASTNAME,
                InputArgument::OPTIONAL,
                'Lastname'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ROLE,
                InputArgument::OPTIONAL,
                'Role'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $username = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_USERNAME, $input, $output);
        $email = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_EMAIL, $input, $output);
        if (($password = $input->getArgument(self::COMMAND_ARGUMENT_PASSWORD)) === null) {
            $dialog = $this->getQuestionHelper();
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $password = $dialog->ask($input, $output, $question);
        }

        $firstname = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_FIRSTNAME, $input, $output);
        $lastname = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_LASTNAME, $input, $output);
        /** @var string|null $roleName */
        $roleName = $input->getArgument(self::COMMAND_ARGUMENT_ROLE);
        if ($roleName !== null) {
            $role = $this->getRoleModel()->load($roleName, 'role_name');
            if (!$role->getId()) {
                $output->writeln('<error>Role was not found</error>');

                return Command::INVALID;
            }
        } else {
            // create new role if not yet existing
            $role = $this->getRoleModel()->load('Development', 'role_name');
            if (!$role->getId()) {
                $role
                    ->setName('Development')
                    ->setRoleType('G')
                    ->save();

                // give "all" privileges to role
                $this
                    ->getRulesModel()
                    ->setRoleId($role->getId())
                    ->setResources(['all'])
                    ->saveRel();

                $output->writeln('<info>The role <comment>Development</comment> was automatically created.</info>');
            }
        }

        // create new user
        $user = $this->getUserModel()
            ->setData([
                'username'  => $username,
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'email'     => $email,
                'password'  => $password,
                'is_active' => 1
            ])
            ->save();

        $user->setRoleIds([$role->getId()])
            ->setRoleUserId($user->getUserId())
            ->saveRelations();

        $output->writeln('<info>User <comment>' . $username . '</comment> successfully created</info>');

        return Command::SUCCESS;
    }
}
