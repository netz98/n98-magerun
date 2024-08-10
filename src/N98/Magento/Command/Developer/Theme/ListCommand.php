<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List theme command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class ListCommand extends AbstractMagentoCommand implements CommandFormatable
{
    /**
     * @var string
     */
    public static $defaultName = 'dev:theme:list';

    /**
     * @var string
     */
    public static $defaultDescription = 'Lists all available themes.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Themes';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['label'];
    }

    /**
     * {@inheritDoc}
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        $packages = $this->getThemes();
        $table = [];
        foreach ($packages as $package => $themes) {
            foreach ($themes as $theme) {
                $table[] = [($package ? $package . '/' : '') . $theme];
            }
        }

        return $table;
    }

    /**
     * @return array
     */
    protected function getThemes(): array
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
