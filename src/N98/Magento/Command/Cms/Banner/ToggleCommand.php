<?php

namespace N98\Magento\Command\Cms\Banner;

use N98\Magento\Command\AbstractMagentoCommand;

use \Enterprise_Banner_Model_Banner;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ToggleCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('cms:banner:toggle')
            ->addArgument('banner_id', InputArgument::REQUIRED, 'Banner ID')
            ->setDescription('Toggle a banner (Enterprise only)')
        ;
    }

    /**
     * @return \Enterprise_Banner_Model_Banner
     */
    protected function _getBannerModel()
    {
        $this->_getModel('enterprise_banner/banner', 'Enterprise_Banner_Model_Banner');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->requireEnterprise($output);
        if ($this->initMagento()) {
            $this->writeSection($output, 'Banner Toggle');
            $bannerId = $input->getArgument('banner_id');

            $banner = $this->_getBannerModel()->load($bannerId);

            if (!$banner->getId()) {
                $output->writeln('<error>Banner was not found</error>');
                return;
            }

            $disabled = !$banner->getIsEnabled();
            $comment = '<comment>Banner</comment> '
            . '<info>' . (!$disabled ? 'disabled' : 'enabled') . '</info>';

            $banner->setIsEnabled($disabled);
            $banner->save();
            $output->writeln($comment);

        }
    }
}