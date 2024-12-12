<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface SubCommandInterface
 *
 * @package N98\Magento\Command\SubCommand
 */
interface SubCommandInterface
{
    public function setConfig(ConfigBag $configBag): void;

    public function setCommandConfig(array $commandConfig): void;

    public function setInput(InputInterface $input): void;

    public function setOutput(OutputInterface $output): void;

    public function getCommand(): AbstractMagentoCommand;

    public function setCommand(AbstractMagentoCommand $magentoCommand): void;

    public function execute(): void;
}
