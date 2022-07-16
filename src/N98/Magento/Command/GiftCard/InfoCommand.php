<?php

namespace N98\Magento\Command\GiftCard;

use Symfony\Component\Console\Input\InputArgument;
use Mage;
use Enterprise_GiftCardAccount_Model_Giftcardaccount as Giftcardaccount;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setDescription('Get gift card account information by code');
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
        $card = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->loadByCode($input->getArgument('code'));
        if (!$card->getId()) {
            $output->writeln('<error>No gift card found for that code</error>');
            return 0;
        }
        $data = [['Gift Card Account ID', $card->getId()], ['Code', $card->getCode()], ['Status', Giftcardaccount::STATUS_ENABLED == $card->getStatus() ? 'Enabled' : 'Disabled'], ['Date Created', $card->getDateCreated()], ['Expiration Date', $card->getDateExpires()], ['Website ID', $card->getWebsiteId()], ['Remaining Balance', $card->getBalance()], ['State', $card->getStateText()], ['Is Redeemable', $card->getIsRedeemable()]];
        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(['Name', 'Value'])
            ->setRows($data)
            ->renderByFormat($output, $data, $input->getOption('format'));
        return 0;
    }
}
