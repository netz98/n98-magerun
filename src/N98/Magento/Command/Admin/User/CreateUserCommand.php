<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:create')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'Firstname')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'Lastname')
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
            if (($username = $input->getArgument('username')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $username = $dialog->ask($output, '<question>Username:</question>');
            }

            // Email
            if (($email = $input->getArgument('email')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $email = $dialog->ask($output, '<question>Email:</question>');
            }

            // Password
            if (($password = $input->getArgument('password')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $password = $dialog->ask($output, '<question>Password:</question>');
            }

            // Firstname
            if (($firstname = $input->getArgument('firstname')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $firstname = $dialog->ask($output, '<question>Firstname:</question>');
            }

            // Lastname
            if (($lastname = $input->getArgument('lastname')) == null) {
                $dialog = $this->getHelperSet()->get('dialog');
                $lastname = $dialog->ask($output, '<question>Lastname:</question>');
            }

            // create new user
            $user = \Mage::getModel('admin/user')
                ->setData(array(
                    'username' => $username,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => $password,
                    'is_active' => 1
                ))->save();

            // create new role
            $role = \Mage::getModel("admin/roles")
                ->setName('Development')
                ->setRoleType('G')
                ->save();

            // give "all" privileges to role
            \Mage::getModel("admin/rules")
                ->setRoleId($role->getId())
                ->setResources(array("all"))
                ->saveRel();

            $user->setRoleIds(array($role->getId()))
                ->setRoleUserId($user->getUserId())
                ->saveRelations();

        }
    }
}