<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            // Username
            if (($username = $input->getArgument('username')) === null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $username = $dialog->ask($output, '<question>Username:</question>');
            }

            // Email
            if (($email = $input->getArgument('email')) === null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $email = $dialog->ask($output, '<question>Email:</question>');
            }

            // Password
            if (($password = $input->getArgument('password')) === null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $password = $dialog->askHiddenResponse($output, '<question>Password:</question>');
            }

            // Firstname
            if (($firstname = $input->getArgument('firstname')) === null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $firstname = $dialog->ask($output, '<question>Firstname:</question>');
            }

            // Lastname
            if (($lastname = $input->getArgument('lastname')) === null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $lastname = $dialog->ask($output, '<question>Lastname:</question>');
            }

            if (($roleName = $input->getArgument('role')) != null) {
                $role = $this->getRoleModel()->load($roleName, 'role_name');
                if(!$role->getId()) {
                    $output->writeln('<error>Role was not found</error>');
                    return;
                }
            } else {
                // create new role if not yet existing
                $role = $this->getRoleModel()->load('Development', 'role_name');
                if(!$role->getId()) {
                    $role->setName('Development')
                        ->setRoleType('G')
                        ->save();

                    $resourceAll = ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) ?
                        \Mage_Backend_Model_Acl_Config::ACL_RESOURCE_ALL : 'all';

                    // give "all" privileges to role
                    $this->getRulesModel()
                        ->setRoleId($role->getId())
                        ->setResources(array($resourceAll))
                        ->saveRel();

                    $output->writeln('<info>The role <comment>Development</comment> was automatically created.</info>');
                }
            }

            // create new user
            $user = $this->getUserModel()
                ->setData(array(
                    'username' => $username,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => $password,
                    'is_active' => 1
                ))->save();

            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
                $user->setRoleId($role->getId())
                    ->save();
            } else {
                $user->setRoleIds(array($role->getId()))
                    ->setRoleUserId($user->getUserId())
                    ->saveRelations();
            }

            $output->writeln('<info>User <comment>' . $username . '</comment> successfully created</info>');
        }
    }
}