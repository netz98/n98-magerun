<?php

namespace N98\Magento\Command\SubCommand;

use N98\Magento\Command\AbstractMagentoCommand;
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
    protected $baseNamespace;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ConfigBag
     */
    protected $config;

    /**
     * @var array
     */
    protected $commandConfig;

    /**
     * @var AbstractMagentoCommand
     */
    protected $command;

    /**
     * @param AbstractMagentoCommand $command
     * @param string $baseNamespace
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array $commandConfig
     * @param ConfigBag $config
     */
    public function __construct(
        AbstractMagentoCommand $command,
        $baseNamespace,
        InputInterface $input,
        OutputInterface $output,
        array $commandConfig,
        ConfigBag $config
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
    public function create($className, $userBaseNamespace = true)
    {
        if ($userBaseNamespace) {
            $className = rtrim($this->baseNamespace, '\\') . '\\' . $className;
        }

        $subCommand = new $className();
        if (!$subCommand instanceof SubCommandInterface) {
            throw new \InvalidArgumentException('Subcommand must implement SubCommandInterface.');
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
    public function getConfig()
    {
        return $this->config;
    }
}
