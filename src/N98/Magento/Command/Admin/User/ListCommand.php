<?php

namespace N98\Magento\Command\Admin\User;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
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
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
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
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        /** @var \Mage_Admin_Model_User $userModel */
        $userModel = $this->getUserModel();
        $userList = $userModel->getCollection();
        $table = [];
        foreach ($userList as $user) {
            $table[] = [$user->getId(), $user->getUsername(), $user->getEmail(), $user->getIsActive() ? 'active' : 'inactive'];
        }

        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(['id', 'username', 'email', 'status'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
