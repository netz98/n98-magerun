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
     * Set data to display in command output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function setData(InputInterface $input, OutputInterface $output): void;
}
