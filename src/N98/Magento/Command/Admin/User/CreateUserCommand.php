<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Helper\DialogHelper;
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $username = $this->getOrAskForArgument('username', $input, $output);
            $email = $this->getOrAskForArgument('email', $input, $output);
            if (($password = $input->getArgument('password')) === null) {
                /* @var $dialog DialogHelper */
                $dialog = $this->getHelper('dialog');
                $password = $dialog->askHiddenResponse($output, '<question>Password:</question>');
            }

            $firstname = $this->getOrAskForArgument('firstname', $input, $output);
            $lastname = $this->getOrAskForArgument('lastname', $input, $output);
            if (($roleName = $input->getArgument('role')) != null) {
                $role = $this->getRoleModel()->load($roleName, 'role_name');
                if (!$role->getId()) {
                    $output->writeln('<error>Role was not found</error>');
                    return;
                }
            } else {
                // create new role if not yet existing
                $role = $this->getRoleModel()->load('Development', 'role_name');
                if (!$role->getId()) {
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
                    'username'  => $username,
                    'firstname' => $firstname,
                    'lastname'  => $lastname,
                    'email'     => $email,
                    'password'  => $password,
                    'is_active' => 1,
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
