<?php

declare(strict_types=1);

namespace N98\Magento\Application;

use Composer\Autoload\ClassLoader;
use InvalidArgumentException;
use N98\Magento\Application;
use N98\Util\ArrayFunctions;
use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Config
 *
 * Class representing the application configuration. Created to factor out configuration related application
 * functionality from @see Application
 *
 * @package N98\Magento\Application
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class Config
{
    public const PSR_0 = 'PSR-0';

    public const PSR_4 = 'PSR-4';

    public const COMMAND_CLASS = 'Symfony\Component\Console\Command\Command';

    private array $config = [];

    private array $partialConfig = [];

    private ?ConfigurationLoader $configurationLoader = null;

    /**
     * @var mixed[]
     */
    private array $initConfig;

    private bool $isPharMode;

    private OutputInterface $output;

    public function __construct(array $initConfig = [], bool $isPharMode = false, ?OutputInterface $output = null)
    {
        $this->initConfig = $initConfig;
        $this->isPharMode = $isPharMode;
        $this->output = $output instanceof OutputInterface ? $output : new NullOutput();
    }

    /**
     * alias magerun command in input from config
     *
     * @return ArgvInput|InputInterface
     */
    public function checkConfigCommandAlias(InputInterface $input)
    {
        foreach ($this->getArray(['commands', 'aliases']) as $alias) {
            if (!is_array($alias)) {
                continue;
            }

            $aliasCommandName = key($alias);
            if ($input->getFirstArgument() !== $aliasCommandName) {
                continue;
            }

            $aliasCommandParams = array_slice(
                BinaryString::trimExplodeEmpty(' ', $alias[$aliasCommandName]),
                1,
            );
            if ([] === $aliasCommandParams) {
                continue;
            }

            // replace command (?) with aliased data
            $oldArgv = $_SERVER['argv'];
            $newArgv = array_merge(
                array_slice($oldArgv, 0, 2),
                $aliasCommandParams,
                array_slice($oldArgv, 2),
            );
            $input = new ArgvInput($newArgv);
        }

        return $input;
    }

    public function registerConfigCommandAlias(Command $command): void
    {
        foreach ($this->getArray(['commands', 'aliases']) as $alias) {
            if (!is_array($alias)) {
                continue;
            }

            $aliasCommandName = key($alias);
            $commandString = $alias[$aliasCommandName];
            [$originalCommand] = explode(' ', $commandString, 2);
            if ($command->getName() !== $originalCommand) {
                continue;
            }

            $command->setAliases(array_merge($command->getAliases(), [$aliasCommandName]));
        }
    }

    public function registerCustomCommands(Application $application): void
    {
        foreach ($this->getArray(['commands', 'customCommands']) as $commandClass) {
            $commandName = null;
            if (is_array($commandClass)) {
                // Support for key => value (name -> class)
                $commandName    = (string) key($commandClass);
                $commandClass   = current($commandClass);
            }

            $command = $this->newCommand($commandClass, $commandName);
            if (is_null($command)) {
                $this->output->writeln(
                    sprintf(
                        '<error>Can not add nonexistent command class "%s" as command to the application</error>',
                        $commandClass,
                    ),
                );
                $this->debugWriteln(
                    'Please check the configuration files contain the correct class-name. If the ' .
                    'class-name is correct, check autoloader configurations.',
                );
            } else {
                $this->debugWriteln(
                    sprintf(
                        '<debug>Add command </debug> <info>%s</info> -> <comment>%s</comment>',
                        $command->getName(),
                        get_class($command),
                    ),
                );
                $application->add($command);
            }
        }
    }

    /**
     * @param mixed $className
     * @throws InvalidArgumentException
     */
    private function newCommand($className, ?string $commandName): ?Command
    {
        if (!is_string($className) && !is_object($className)) {
            throw new InvalidArgumentException(
                sprintf('Command classname must be string, %s given', gettype($className)),
            );
        }

        if (is_string($className) && !class_exists($className)) {
            return null;
        }

        if (false === is_subclass_of($className, self::COMMAND_CLASS, true)) {
            $className = is_object($className) ? get_class($className) : $className;
            throw new InvalidArgumentException(
                sprintf('Class "%s" is not a Command (subclass of "%s")', $className, self::COMMAND_CLASS),
            );
        }

        /** @var Command $command */
        $command = new $className();
        if (null !== $commandName) {
            $command->setName($commandName);
        }

        return $command;
    }

    /**
     * Adds autoloader prefixes from user's config
     */
    public function registerCustomAutoloaders(ClassLoader $classLoader): void
    {
        $mask = '<debug>Registered %s autoloader </debug> <info>%s</info> -> <comment>%s</comment>';

        foreach ($this->getArray('autoloaders') as $prefix => $paths) {
            $this->debugWriteln(sprintf($mask, self::PSR_0, OutputFormatter::escape($prefix), implode(',', (array) $paths)));
            $classLoader->add($prefix, $paths);
        }

        foreach ($this->getArray('autoloaders_psr4') as $prefix => $paths) {
            $this->debugWriteln(sprintf($mask, self::PSR_4, OutputFormatter::escape($prefix), implode(',', (array) $paths)));
            $classLoader->addPsr4($prefix, $paths);
        }
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get config array (whole or in part)
     *
     * @param string|array $key
     */
    public function getConfig($key = null): array
    {
        if (null === $key) {
            return $this->config;
        }

        return $this->getArray($key);
    }

    public function setLoader(ConfigurationLoader $configurationLoader): void
    {
        $this->configurationLoader = $configurationLoader;
    }

    public function getLoader(): ConfigurationLoader
    {
        if (!$this->configurationLoader instanceof ConfigurationLoader) {
            $this->configurationLoader = $this->createLoader($this->initConfig, $this->isPharMode, $this->output);
            $this->initConfig = [];
        }

        return $this->configurationLoader;
    }

    public function load(): void
    {
        $this->config = $this->getLoader()->toArray();
    }

    public function loadPartialConfig(bool $loadExternalConfig): void
    {
        $configurationLoader = $this->getLoader();
        $this->partialConfig = $configurationLoader->getPartialConfig($loadExternalConfig);
    }

    /**
     * Get names of sub-folders to be scanned during Magento detection
     */
    public function getDetectSubFolders(): array
    {
        if (isset($this->partialConfig['detect']['subFolders'])) {
            return $this->partialConfig['detect']['subFolders'];
        }

        return [];
    }

    public function createLoader(array $initConfig, bool $isPharMode, OutputInterface $output): ConfigurationLoader
    {
        $config = ArrayFunctions::mergeArrays($this->config, $initConfig);
        return new ConfigurationLoader($config, $isPharMode, $output);
    }

    private function debugWriteln(string $message): void
    {
        $output = $this->output;
        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $output->writeln($message);
        }
    }

    /**
     * Get array from config, default to an empty array if not set
     *
     * @param string|array $key
     */
    private function getArray($key, array $default = []): array
    {
        $result = $this->traverse((array) $key);
        if (null === $result) {
            return $default;
        }

        return $result;
    }

    private function traverse(array $keys): ?array
    {
        $anchor = &$this->config;
        foreach ($keys as $key) {
            if (!is_array($anchor)) {
                return null;
            }

            if (!isset($anchor[$key])) {
                return null;
            }

            $anchor = &$anchor[$key];
        }

        return $anchor;
    }
}
