<?php

namespace N98\Magento\Command\Cms\Banner;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ToggleCommand
 *
 * Only testable with closed source enterprise edition
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\Cms\Banner
 */
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
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }

    /**
     * @return \Enterprise_Banner_Model_Banner
     */
    protected function _getBannerModel()
    {
        return $this->_getModel('enterprise_banner/banner', '\Enterprise_Banner_Model_Banner');
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
        $this->requireEnterprise($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $this->writeSection($output, 'Banner Toggle');
        $bannerId = $input->getArgument('banner_id');

        $banner = $this->_getBannerModel()->load($bannerId);

        if (!$banner->getId()) {
            $output->writeln('<error>Banner was not found</error>');
            return 0;
        }

        $disabled = !$banner->getIsEnabled();
        $comment = '<comment>Banner</comment> '
        . '<info>' . (!$disabled ? 'disabled' : 'enabled') . '</info>';

        $banner->setIsEnabled($disabled);
        $banner->save();
        $output->writeln($comment);
        return 0;
    }
}
