<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use Mage_Core_Model_Design_Package;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List theme command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
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
            return Command::INVALID;
        }

        $packages = $this->getThemes();
        $table = [];
        foreach ($packages as $package => $themes) {
            foreach ($themes as $theme) {
                $table[] = [($package !== 0 && ($package !== '' && $package !== '0') ? $package . '/' : '') . $theme];
            }
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Theme'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }

    protected function getThemes(): array
    {
        /** @var Mage_Core_Model_Design_Package $model */
        $model = Mage::getModel('core/design_package');
        return $model->getThemeList();
    }
}
