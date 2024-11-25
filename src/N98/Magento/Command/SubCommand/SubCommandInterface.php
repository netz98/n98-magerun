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
    /**
     * @return void
     */
    public function setConfig(ConfigBag $configBag);

    /**
     * @return void
     */
    public function setCommandConfig(array $commandConfig);

    /**
     * @return void
     */
    public function setInput(InputInterface $input);

    /**
     * @return void
     */
    public function setOutput(OutputInterface $output);

    /**
     * @return AbstractMagentoCommand
     */
    public function getCommand();

    /**
     * @return void
     */
    public function setCommand(AbstractMagentoCommand $magentoCommand);

    /**
     * @return void
     */
    public function execute();
}
