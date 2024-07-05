<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\Core\Design;
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
     * {@inheritDoc}
     */
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Theme'];
    }

    /**
     * {@inheritdoc}
     *
     * @uses Design\Package::getModel()
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            foreach (Design\Package::getModel()->getThemeList() as $package => $themes) {
                foreach ($themes as $theme) {
                    $this->data[] = [
                        ($package ? $package . '/' : '') . $theme
                    ];
                }
            }
        }

        return $this->data;
    }
}
