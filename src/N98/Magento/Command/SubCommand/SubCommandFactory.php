<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SubCommandFactory
 *
 * @package N98\Magento\Command\SubCommand
 */
class SubCommandFactory
{
    protected string $baseNamespace;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected ConfigBag $config;

    protected array $commandConfig;

    protected AbstractMagentoCommand $command;

    public function __construct(
        AbstractMagentoCommand $magentoCommand,
        string $baseNamespace,
        InputInterface $input,
        OutputInterface $output,
        array $commandConfig,
        ConfigBag $configBag
    ) {
        $this->baseNamespace = $baseNamespace;
        $this->command = $magentoCommand;
        $this->input = $input;
        $this->output = $output;
        $this->commandConfig = $commandConfig;
        $this->config = $configBag;
    }

    public function create(string $className, bool $userBaseNamespace = true): SubCommandInterface
    {
        if ($userBaseNamespace) {
            $className = rtrim($this->baseNamespace, '\\') . '\\' . $className;
        }

        $subCommand = new $className();
        if (!$subCommand instanceof SubCommandInterface) {
            throw new InvalidArgumentException('Subcommand must implement SubCommandInterface.');
        }

        // Inject objects
        $subCommand->setCommand($this->command);
        $subCommand->setInput($this->input);
        $subCommand->setOutput($this->output);
        $subCommand->setConfig($this->config);
        $subCommand->setCommandConfig($this->commandConfig);

        return $subCommand;
    }

    public function getConfig(): ConfigBag
    {
        return $this->config;
    }
}
