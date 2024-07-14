<?php

namespace N98\Magento\Command\GiftCard;

use Mage;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Gift card info command
 *
 * @package N98\Magento\Command\GiftCard
 */
class InfoCommand extends AbstractGiftCardCommand
{
    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('giftcard:info')
            ->addArgument('code', InputArgument::REQUIRED, 'Gift card code')
            ->addFormatOption()
            ->setDescription('Get gift card account information by code');
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
        $this->requireEnterprise($output);

        if (!class_exists('Enterprise_GiftCardAccount_Model_Giftcardaccount')) {
            return 0;
        }

        if (!$this->initMagento()) {
            return 0;
        }

        /** @var \Enterprise_GiftCardAccount_Model_Giftcardaccount $card */
        $card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByCode($input->getArgument('code'));
        if (!$card->getId()) {
            $output->writeln('<error>No gift card found for that code</error>');
            return 0;
        }
        $data = [
            ['Gift Card Account ID', $card->getId()],
            ['Code', $card->getCode()],
            ['Status', \Enterprise_GiftCardAccount_Model_Giftcardaccount::STATUS_ENABLED == $card->getStatus() ? 'Enabled' : 'Disabled'],
            ['Date Created', $card->getDateCreated()],
            ['Expiration Date', $card->getDateExpires()],
            ['Website ID', $card->getWebsiteId()],
            ['Remaining Balance', $card->getBalance()],
            ['State', $card->getStateText()],
            ['Is Redeemable', $card->getIsRedeemable()]
        ];
        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(['Name', 'Value'])
            ->setRows($data)
            ->renderByFormat($output, $data, $input->getOption('format'));
        return 0;
    }
}
