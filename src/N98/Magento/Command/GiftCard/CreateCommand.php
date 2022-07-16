<?php

namespace N98\Magento\Command\GiftCard;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractGiftCardCommand
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
            ->addArgument(
                'amount',
                InputArgument::REQUIRED,
                'Amount for new gift card'
            )
            ->addOption(
                'website',
                null,
                InputOption::VALUE_OPTIONAL,
                'Website ID to attach gift card to'
            )
            ->setDescription('Create a gift card with a specified amount');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }
        $data = ['status'        => 1, 'is_redeemable' => 1, 'website_id'    => $input->getOption('website') ?: Mage::app()->getStore(true)->getWebsiteId(), 'balance'       => $input->getArgument('amount')];
        $id = Mage::getModel('enterprise_giftcardaccount/api')->create($data);
        if (!$id) {
            $output->writeln('<error>Failed to create gift card</error>');
        }
        $code = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
            ->load($id)
            ->getCode();
        $output->writeln('<info>Gift card <comment>' . $code . '</comment> was created</info>');
        return 0;
    }
}
