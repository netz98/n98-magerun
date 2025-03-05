<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface CommandAware
 *
 * @package N98\Magento\Command
 */
interface CommandFormatable
{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string[]
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    public function getListData(InputInterface $input, OutputInterface $output): array;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string;
}
