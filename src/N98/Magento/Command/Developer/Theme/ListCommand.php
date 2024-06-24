<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Theme list command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class ListCommand extends AbstractCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Themes';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:theme:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all available themes.';

    /**
     * {@inheritdoc}
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];

        foreach ($this->getThemes() as $package => $themes) {
            foreach ($themes as $theme) {
                $this->data[] = [
                    'Theme' => ($package ? $package . '/' : '') . $theme
                ];
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getThemes(): array
    {
        return Mage::getModel('core/design_package')->getThemeList();
    }
}
