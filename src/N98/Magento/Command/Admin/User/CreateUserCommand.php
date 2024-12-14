<?php

declare(strict_types=1);

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Create admin user command
 *
 * @package N98\Magento\Command\Admin\User
 */
class CreateUserCommand extends AbstractAdminUserCommand
{
    protected function configure(): void
    {
        $this
            ->setName('admin:user:create')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email, empty string = generate')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'Firstname')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'Lastname')
            ->addArgument('role', InputArgument::OPTIONAL, 'Role')
            ->setDescription('Create admin user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $username = $this->getOrAskForArgument('username', $input, $output);
            $email = $this->getOrAskForArgument('email', $input, $output);
            if (($password = $input->getArgument('password')) === null) {
                $dialog = $this->getQuestionHelper();
                $question = new Question('<question>Password:</question> ');
                $question->setHidden(true);
                $password = $dialog->ask($input, $output, $question);
            }

            $firstname = $this->getOrAskForArgument('firstname', $input, $output);
            $lastname = $this->getOrAskForArgument('lastname', $input, $output);
            if (($roleName = $input->getArgument('role')) != null) {
                $role = $this->getRoleModel()->load($roleName, 'role_name');
                if (!$role->getId()) {
                    $output->writeln('<error>Role was not found</error>');
                    return Command::FAILURE;
                }
            } else {
                // create new role if not yet existing
                $role = $this->getRoleModel()->load('Development', 'role_name');
                if (!$role->getId()) {
                    $role->setName('Development') # @phpstan-ignore method.notFound (missing in current OpenMage)
                        ->setRoleType('G')
                        ->save();

                    // give "all" privileges to role
                    $this->getRulesModel()
                        ->setRoleId((int) $role->getId())
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
                    'is_active' => 1,
                ])->save();

            $user->setRoleIds([$role->getId()])
                ->setRoleUserId($user->getUserId())
                ->saveRelations();

            $output->writeln('<info>User <comment>' . $username . '</comment> successfully created</info>');
        }

        return Command::SUCCESS;
    }
}
