<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:list')
            ->setDescription('List admin users.')
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
            $userList = $this->getUserModel()->getCollection();
            $table = array();
            foreach ($userList as $user) {
                $table[] = array(
                    'id'       => '  ' . $user->getId(),
                    'username' => $user->getUsername(),
                    'email'    => $user->getEmail(),
                    'status'   => $user->getIsActive() ? 'active' : 'inactive',
                );
            }
            $this->getHelper('table')->write($output, $table);
        }
    }
}