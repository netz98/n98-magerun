<?php
/*
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

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
 * functionality from @see \N98\Magento\Application
 *
 * @package N98\Magento\Application
 */
class Config
{
    const PSR_0 = 'PSR-0';
    const PSR_4 = 'PSR-4';

    const COMMAND_CLASS = 'Symfony\Component\Console\Command\Command';

    /**
     * @var array config data
     */
    private $config = array();

    /**
     * @var array
     */
    private $partialConfig = array();

    /**
     * @var ConfigurationLoader
     */
    private $loader;

    /**
     * @var array
     */
    private $initConfig = array();

    /**
     * @var boolean
     */
    private $isPharMode;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Config constructor.
     *
     * @param array $initConfig
     * @param bool $isPharMode
     * @param OutputInterface $output [optional]
     */
    public function __construct(array $initConfig = array(), $isPharMode = false, OutputInterface $output = null)
    {
        $this->initConfig = $initConfig;
        $this->isPharMode = (bool) $isPharMode;
        $this->output = $output ?: new NullOutput();
    }

    /**
     * alias magerun command in input from config
     *
     * @param InputInterface $input
     * @return ArgvInput|InputInterface
     */
    public function checkConfigCommandAlias(InputInterface $input)
    {
        foreach ($this->getArray(array('commands', 'aliases')) as $alias) {
            if (!is_array($alias)) {
                continue;
            }
            $aliasCommandName = key($alias);
            if ($input->getFirstArgument() !== $aliasCommandName) {
                continue;
            }
            $aliasCommandParams = array_slice(
                BinaryString::trimExplodeEmpty(' ', $alias[$aliasCommandName]),
                1
            );
            if (0 === count($aliasCommandParams)) {
                continue;
            }

            // replace command (?) with aliased data
            $oldArgv = $_SERVER['argv'];
            $newArgv = array_merge(
                array_slice($oldArgv, 0, 2),
                $aliasCommandParams,
                array_slice($oldArgv, 2)
            );
            $input = new ArgvInput($newArgv);
        }

        return $input;
    }

    /**
     * @param Command $command
     */
    public function registerConfigCommandAlias(Command $command)
    {
        foreach ($this->getArray(array('commands', 'aliases')) as $alias) {
            if (!is_array($alias)) {
                continue;
            }

            $aliasCommandName = key($alias);
            $commandString = $alias[$aliasCommandName];
            list($originalCommand) = explode(' ', $commandString, 2);
            if ($command->getName() !== $originalCommand) {
                continue;
            }

            $command->setAliases(array_merge($command->getAliases(), array($aliasCommandName)));
        }
    }

    /**
     * @param Application $application
     */
    public function registerCustomCommands(Application $application)
    {
        foreach ($this->getArray(array('commands', 'customCommands')) as $commandClass) {
            $commandName = null;
            if (is_array($commandClass)) {
                // Support for key => value (name -> class)
                $commandName = key($commandClass);
                $commandClass = current($commandClass);
            }
            if (null === $command = $this->newCommand($commandClass, $commandName)) {
                $this->output->writeln(
                    sprintf(
                        '<error>Can not add nonexistent command class "%s" as command to the application</error>',
                        $commandClass,
                        $commandName
                    )
                );
                $this->debugWriteln(
                    'Please check the configuration files contain the correct class-name. If the ' .
                    'class-name is correct, check autoloader configurations.'
                );
            } else {
                $this->debugWriteln(
                    sprintf(
                        '<debug>Add command </debug> <info>%s</info> -> <comment>%s</comment>',
                        $command->getName(),
                        get_class($command)
                    )
                );
                $application->add($command);
            }
        }
    }

    /**
     * @param string $className
     * @param string|null $commandName
     * @return Command
     * @throws InvalidArgumentException
     */
    private function newCommand($className, $commandName)
    {
        /** @var Command $command */
        if (!(is_string($className) || is_object($className))) {
            throw new InvalidArgumentException(
                sprintf('Command classname must be string, %s given', gettype($className))
            );
        }

        if (!class_exists($className)) {
            return null;
        }

        if (false === is_subclass_of($className, self::COMMAND_CLASS, true)) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" is not a Command (subclass of "%s")', $className, self::COMMAND_CLASS)
            );
        }

        $command = new $className();
        if (null !== $commandName) {
            $command->setName($commandName);
        }

        return $command;
    }

    /**
     * Adds autoloader prefixes from user's config
     *
     * @param ClassLoader $autoloader
     */
    public function registerCustomAutoloaders(ClassLoader $autoloader)
    {
        $mask = '<debug>Registered %s autoloader </debug> <info>%s</info> -> <comment>%s</comment>';

        foreach ($this->getArray('autoloaders') as $prefix => $paths) {
            $paths = (array) $paths;
            $this->debugWriteln(sprintf($mask, self::PSR_0, OutputFormatter::escape($prefix), implode(",", $paths)));
            $autoloader->add($prefix, $paths);
        }

        foreach ($this->getArray('autoloaders_psr4') as $prefix => $paths) {
            $paths = (array) $paths;
            $this->debugWriteln(sprintf($mask, self::PSR_4, OutputFormatter::escape($prefix), implode(",", $paths)));
            $autoloader->addPsr4($prefix, $paths);
        }
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get config array (whole or in part)
     *
     * @param string|array $key
     * @return array
     */
    public function getConfig($key = null)
    {
        if (null === $key) {
            return $this->config;
        }

        return $this->getArray($key);
    }

    /**
     * @param ConfigurationLoader $configurationLoader
     */
    public function setLoader(ConfigurationLoader $configurationLoader)
    {
        $this->loader = $configurationLoader;
    }

    /**
     * @return ConfigurationLoader
     */
    public function getLoader()
    {
        if (!$this->loader) {
            $this->loader = $this->createLoader($this->initConfig, $this->isPharMode, $this->output);
            $this->initConfig = array();
        }

        return $this->loader;
    }

    public function load()
    {
        $this->config = $this->getLoader()->toArray();
    }

    /**
     * @param bool $loadExternalConfig
     */
    public function loadPartialConfig($loadExternalConfig)
    {
        $loader = $this->getLoader();
        $this->partialConfig = $loader->getPartialConfig($loadExternalConfig);
    }

    /**
     * Get names of sub-folders to be scanned during Magento detection
     *
     * @return array
     */
    public function getDetectSubFolders()
    {
        if (isset($this->partialConfig['detect']['subFolders'])) {
            return $this->partialConfig['detect']['subFolders'];
        }

        return array();
    }

    /**
     * @param array $initConfig
     * @param bool $isPharMode
     * @param OutputInterface $output
     *
     * @return ConfigurationLoader
     */
    public function createLoader(array $initConfig, $isPharMode, OutputInterface $output)
    {
        $config = ArrayFunctions::mergeArrays($this->config, $initConfig);

        $loader = new ConfigurationLoader($config, $isPharMode, $output);

        return $loader;
    }

    /**
     * @param string $message
     */
    private function debugWriteln($message)
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
     * @param array $default [optional]
     * @return array
     */
    private function getArray($key, $default = array())
    {
        $result = $this->traverse((array) $key);
        if (null === $result) {
            return $default;
        }

        return $result;
    }

    private function traverse(array $keys)
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
