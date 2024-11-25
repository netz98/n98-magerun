<?php

declare(strict_types=1);

namespace N98\Magento\Command\SubCommand;

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
     * @param string $baseNamespace
     */
    public function __construct(
        AbstractMagentoCommand $magentoCommand,
        $baseNamespace,
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
