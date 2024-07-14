<?php

namespace N98\Magento\Command\Admin\User;

use Mage_Backend_Model_Acl_Config;
use Symfony\Component\Console\Helper\QuestionHelper;
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
    protected function configure()
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $username = $this->getOrAskForArgument('username', $input, $output);
            $email = $this->getOrAskForArgument('email', $input, $output);
            if (($password = $input->getArgument('password')) === null) {
                /* @var QuestionHelper $dialog */
                $dialog = $this->getHelper('question');
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
                    return 0;
                }
            } else {
                // create new role if not yet existing
                $role = $this->getRoleModel()->load('Development', 'role_name');
                if (!$role->getId()) {
                    $role->setName('Development')
                        ->setRoleType('G')
                        ->save();

                    // @todo check cmuench correct class name?
                    $resourceAll = ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) ?
                        Mage_Backend_Model_Acl_Config::ACL_RESOURCE_ALL : 'all';

                    // give "all" privileges to role
                    $this->getRulesModel()
                        ->setRoleId($role->getId())
                        ->setResources([$resourceAll])
                        ->saveRel();

                    $output->writeln('<info>The role <comment>Development</comment> was automatically created.</info>');
                }
            }

            // create new user
            $user = $this->getUserModel()
                ->setData(['username'  => $username, 'firstname' => $firstname, 'lastname'  => $lastname, 'email'     => $email, 'password'  => $password, 'is_active' => 1])->save();

            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
                $user->setRoleId($role->getId())
                    ->save();
            } else {
                $user->setRoleIds([$role->getId()])
                    ->setRoleUserId($user->getUserId())
                    ->saveRelations();
            }

            $output->writeln('<info>User <comment>' . $username . '</comment> successfully created</info>');
        }
        return 0;
    }
}
