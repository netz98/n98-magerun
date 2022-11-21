<?php

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:theme:list')
            ->setDescription('Lists all available themes')
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        $packages = $this->getThemes();
        $table = [];
        foreach ($packages as $package => $themes) {
            foreach ($themes as $theme) {
                $table[] = [($package ? $package . '/' : '') . $theme];
            }
        }

        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(['Theme'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }

    /**
     * @return array
     */
    protected function getThemes()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            $collection = Mage::getModel('Mage_Core_Model_Theme')->getLabelsCollection();
            $themes = [];
            foreach ($collection as $theme) {
                $themes[] = $theme['label'];
            }

            return [$themes];
        }

        return Mage::getModel('core/design_package')->getThemeList();
    }
}
