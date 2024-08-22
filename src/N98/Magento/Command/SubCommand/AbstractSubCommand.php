<?php

namespace N98\Magento\Command\SubCommand;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AbstractSubCommand
 *
 * @package N98\Magento\Command\SubCommand
 */
abstract class AbstractSubCommand implements SubCommandInterface
{
    /**
     * @var ConfigBag
     */
    protected $config;

    /**
     * @var array
     */
    protected $commandConfig;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var AbstractMagentoCommand
     */
    protected $command;

    /**
     * @param ConfigBag $config
     */
    public function setConfig(ConfigBag $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $commandConfig
     */
    public function setCommandConfig(array $commandConfig)
    {
        $this->commandConfig = $commandConfig;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return AbstractMagentoCommand
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param AbstractMagentoCommand $command
     */
    public function setCommand(AbstractMagentoCommand $command)
    {
        $this->command = $command;
    }

    /**
     * @return void
     */
    abstract public function execute();

    /**
     * @param string $name of the optional option
     * @param string $question to ask in case the option is not available
     * @param bool $default value (true means yes, false no), optional, defaults to true
     * @return bool
     */
    final protected function getOptionalBooleanOption($name, $question, $default = true)
    {
        if ($this->input->getOption($name) !== null) {
            $flag = $this->getCommand()->parseBoolOption($this->input->getOption($name));

            return $flag;
        } else {
            $questionHelper = $this->getCommand()->getQuestionHelper();

            $question = new Question(
                sprintf(
                    '<question>%s</question> <comment>[%s]',
                    $question,
                    $default
                ),
                $default
            );

            $flag = $questionHelper->ask(
                $this->input,
                $this->output,
                $question
            );

            return $flag;
        }
    }

    /**
     * @param string $name of flag/option
     * @param bool $default value for flag/option if set but with no value
     * @return bool
     */
    final protected function hasFlagOrOptionalBoolOption($name, $default = true)
    {
        if (!$this->input->hasOption($name)) {
            return false;
        }

        $value = $this->input->getOption($name);
        if (null === $value) {
            return (bool) $default;
        }

        return (bool) $this->getCommand()->parseBoolOption($value);
    }
}
