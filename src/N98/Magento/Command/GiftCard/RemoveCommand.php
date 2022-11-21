<?php

namespace N98\Magento\Command\GiftCard;

use Mage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends AbstractGiftCardCommand
{
    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('giftcard:remove')
            ->addArgument('code', InputArgument::REQUIRED, 'Gift card code')
            ->setDescription('Remove a gift card account by code');
    }

    /**
     * @param InputInterface  $input
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
        $accounts = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->getCollection()
            ->addFieldToFilter('code', $input->getArgument('code'));
        if (!$accounts->count()) {
            $output->writeln('<info>No gift cards with matching code found</info>');
        } else {
            foreach ($accounts as $account) {
                $id = $account->getId();
                $account->delete();
                $output->writeln('<info>Deleted gift card account id <comment>' . $id . '</comment></info>');
            }
        }
        return 0;
    }
}
