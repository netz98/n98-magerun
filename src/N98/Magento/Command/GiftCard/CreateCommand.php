<?php

namespace N98\Magento\Command\GiftCard;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CreateCommand extends AbstractMagentoCommand
{
    /**
     * Setup
     * 
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('giftcard:create')
            ->addArgument('amount', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Amount for new gift card')
            ->addOption('website', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Website ID to attach gift card to')
            ->setDescription('Create a gift card with a specified amount');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $data = array(
                'status'        => 1,
                'is_redeemable' => 1,
                'website_id'    => $input->getOption('website')
                    ? $input->getOption('website')
                    : \Mage::app()->getStore(true)->getWebsiteId(),
                'balance'       => $input->getArgument('amount')
            );
            $id = \Mage::getModel('enterprise_giftcardaccount/api')->create($data);
            if (!$id) {
                $output->writeln('<error>Failed to create gift card</error>');
            }
            $code = \Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
                ->load($id)
                ->getCode();

            $output->writeln('<info>Gift card <comment>' . $code . '</comment> was created</info>');

            /*
            if ($amount = $input->getArgument('amount')) {
                $user = \Mage::getModel('admin/user')->loadByUsername($username);
                if (!$user || !$user->getId()) {
                     $output->writeln('<error>Couldn\'t find admin ' . $username . '</error>');
                     return;
                }
                \Mage::getResourceModel('enterprise_pci/admin_user')->unlock($user->getId());
                $output->writeln('<info><comment>' . $username . '</comment> unlocked</info>');
                return;
            }
            \Mage::getResourceModel('enterprise_pci/admin_user')->unlock(
                \Mage::getModel('admin/user')
                    ->getCollection()
                    ->getAllIds()
            );
            $output->writeln('<info><comment>All admins</comment> unlocked</info>');
            */
        }
    }
}