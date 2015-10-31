<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class ListCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:list')
            ->setDescription('List admin users.')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
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
                    $user->getId(),
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getIsActive() ? 'active' : 'inactive',
                );
            }
            $this->getHelper('table')
                ->setHeaders(array('id', 'username', 'email', 'status'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }
}
