<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use Mage;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Theme list command
 *
 * @package N98\Magento\Command\Developer\Theme
 */
class ListCommand extends AbstractCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Themes';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:theme:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all available themes.';

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            foreach ($this->getThemes() as $package => $themes) {
                foreach ($themes as $theme) {
                    $this->data[] = [
                        'Theme' => ($package ? $package . '/' : '') . $theme
                    ];
                }
            }
        }

        return $this->data;
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getThemes(): array
    {
        return Mage::getModel('core/design_package')->getThemeList();
    }
}
