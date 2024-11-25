<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use Mage_Core_Model_Design_Package;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List theme command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:theme:list')
            ->setDescription('Lists all available themes')
            ->addFormatOption()
        ;
    }

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

        $tableHelper = $this->getTableHelper();
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
        /** @var Mage_Core_Model_Design_Package $model */
        $model = Mage::getModel('core/design_package');
        return $model->getThemeList();
    }
}
