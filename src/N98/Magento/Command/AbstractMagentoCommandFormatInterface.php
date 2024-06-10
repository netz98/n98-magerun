<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface AbstractMagentoCommandFormatInterface
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array<int|string, array<string, string>>
     */
    public function getData(InputInterface $input, OutputInterface $output): array;
}
