<?php

namespace N98\Magento\Command\GiftCard\Pool;

use Mage;
use N98\Magento\Command\GiftCard\AbstractGiftCardCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends AbstractGiftCardCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('giftcard:pool:generate')
            ->setDescription('Generate giftcard pool');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        Mage::getModel('enterprise_giftcardaccount/pool')->generatePool();
        $output->writeln('<comment>New pool was generated.</comment>');
        return 0;
    }
}
