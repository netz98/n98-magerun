<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface SubCommandInterface
 * @package N98\Magento\Command\SubCommand
 */
interface SubCommandInterface
{
    /**
     * @param ConfigBag $config
     * @return void
     */
    public function setConfig(ConfigBag $config);

    /**
     * @param array $commandConfig
     * @return void
     */
    public function setCommandConfig(array $commandConfig);

    /**
     * @param InputInterface $input
     * @return void
     */
    public function setInput(InputInterface $input);

    /**
     * @param OutputInterface $output
     * @return void
     */
    public function setOutput(OutputInterface $output);

    /**
     * @return AbstractCommand
     */
    public function getCommand();

    /**
     * @param AbstractCommand $command
     * @return void
     */
    public function setCommand(AbstractCommand $command);

    /**
     * @return void
     */
    public function execute();
}
