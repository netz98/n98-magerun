<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

use InvalidArgumentException;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SubCommandFactory
 * @package N98\Magento\Command\SubCommand
 */
class SubCommandFactory
{
    /**
     * @var string
     */
    protected string $baseNamespace;

    /**
     * @var InputInterface
     */
    protected InputInterface $input;

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var ConfigBag
     */
    protected ConfigBag $config;

    /**
     * @var array
     */
    protected array $commandConfig;

    /**
     * @var AbstractCommand
     */
    protected AbstractCommand $command;

    /**
     * @param AbstractCommand $command
     * @param string $baseNamespace
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array $commandConfig
     * @param ConfigBag $config
     */
    public function __construct(
        AbstractCommand $command,
        string          $baseNamespace,
        InputInterface  $input,
        OutputInterface $output,
        array           $commandConfig,
        ConfigBag       $config
    ) {
        $this->baseNamespace = $baseNamespace;
        $this->command = $command;
        $this->input = $input;
        $this->output = $output;
        $this->commandConfig = $commandConfig;
        $this->config = $config;
    }

    /**
     * @param string $className
     * @param bool $userBaseNamespace
     * @return SubCommandInterface
     */
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

    /**
     * @return ConfigBag
     */
    public function getConfig(): ConfigBag
    {
        return $this->config;
    }
}
