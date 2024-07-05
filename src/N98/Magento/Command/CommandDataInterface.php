<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface CommandDataInterface
 *
 * @package N98\Magento\Command
 */
interface CommandDataInterface
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return string[]
     */
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array;

    /**
     * Set data to display in command output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array<int|string, array<int|string, int|string>>
     */
    public function getData(InputInterface $input, OutputInterface $output): array;
}
