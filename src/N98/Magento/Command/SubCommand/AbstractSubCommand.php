<?php

declare(strict_types=1);

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
    protected ConfigBag $config;

    protected array $commandConfig;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected AbstractMagentoCommand $command;

    public function setConfig(ConfigBag $configBag): void
    {
        $this->config = $configBag;
    }

    public function setCommandConfig(array $commandConfig): void
    {
        $this->commandConfig = $commandConfig;
    }

    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getCommand(): AbstractMagentoCommand
    {
        return $this->command;
    }

    public function setCommand(AbstractMagentoCommand $magentoCommand): void
    {
        $this->command = $magentoCommand;
    }

    abstract public function execute(): void;

    /**
     * @param string $name of the optional option
     * @param string $question to ask in case the option is not available
     * @param string|bool $default value (true means yes, false no), optional, defaults to true
     */
    final protected function getOptionalBooleanOption(string $name, string $question, $default = true): bool
    {
        if ($this->input->getOption($name) !== null) {
            return $this->getCommand()->parseBoolOption($this->input->getOption($name));
        }

        $questionHelper = $this->getCommand()->getQuestionHelper();
        $question = new Question(
            sprintf(
                '<question>%s</question> <comment>[%s]',
                $question,
                $default,
            ),
            $default,
        );
        return $questionHelper->ask(
            $this->input,
            $this->output,
            $question,
        );
    }

    /**
     * @param string $name of flag/option
     * @param bool $default value for flag/option if set but with no value
     */
    final protected function hasFlagOrOptionalBoolOption(string $name, bool $default = true): bool
    {
        if (!$this->input->hasOption($name)) {
            return false;
        }

        $value = $this->input->getOption($name);

        if (is_null($value)) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return $this->getCommand()->parseBoolOption((string) $value);
    }
}
