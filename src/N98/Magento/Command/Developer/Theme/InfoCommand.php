<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class InfoCommand
 * @codeCoverageIgnore Command is currently not implemented
 * @package N98\Magento\Command\Developer\Theme
 */
class InfoCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:theme:info')
            ->addArgument('theme', InputArgument::REQUIRED, 'Your theme')
            ->setDescription('Infos about a theme');
    }

    /**
     * @param \Symfony\Component\Console\Input\\Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\\Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento($output)) {
        }
    }
}