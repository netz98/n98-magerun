<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use Exception;
use Mage;
use Mage_Core_Model_Config;
use Mage_Core_Model_Config_Element;
use Mage_Core_Model_Resource_Resource;
use Mage_Core_Model_Resource_Setup;
use N98\Magento\Command\AbstractMagentoCommand;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Varien_Simplexml_Config;
use Varien_Simplexml_Element;

/**
 * Run incremental setup command
 *
 * @package N98\Magento\Command\System\Setup
 * @codeCoverageIgnore
 */
class IncrementalCommand extends AbstractMagentoCommand
{
    public const TYPE_MIGRATION_STRUCTURE = 'structure';

    public const TYPE_MIGRATION_DATA = 'data';

    protected OutputInterface $_output;

    protected InputInterface $_input;

    /**
     * Holds our copy of the global config.
     *
     * Loaded to avoid grabbing the cached version, and so
     * we still have all our original information when we
     * destroy the real configuration
     */
    protected Varien_Simplexml_Config $_secondConfig;

    /**
     * @var mixed $_eventStash
     */
    protected $_eventStash;

    protected array $_config;

    protected function configure(): void
    {
        $this
            ->setName('sys:setup:incremental')
            ->setDescription('List new setup scripts to run, then runs one script')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stops execution of script on error');
    }

    public function getHelp(): string
    {
        return <<<HELP
Examines an un-cached configuration tree and determines which
structure and data setup resource scripts need to run, and then runs them.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->_config = $this->getCommandConfig();

        //sets output so we can access it from all methods
        $this->_setOutput($output);
        $this->_setInput($input);
        if (false === $this->_init()) {
            return Command::INVALID;
        }

        $needsUpdate = $this->_analyzeSetupResourceClasses();

        if ($needsUpdate === []) {
            return Command::FAILURE;
        }

        $this->_listDetailedUpdateInformation($needsUpdate);
        $this->_runAllStructureUpdates($needsUpdate);
        $output->writeln('We have run all the setup resource scripts.');
        return Command::SUCCESS;
    }

    protected function _loadSecondConfig(): void
    {
        $mageCoreModelConfig = new Mage_Core_Model_Config();
        $mageCoreModelConfig->loadBase();
        //get app/etc
        $this->_secondConfig = Mage::getConfig()->loadModulesConfiguration('config.xml', $mageCoreModelConfig);
    }

    protected function _getAllSetupResourceObjects(): array
    {
        $config = $this->_secondConfig;
        $setupResources = [];

        $resourcesNode = $config->getNode('global/resources');
        if (!$resourcesNode) {
            return $setupResources;
        }

        /** @var Mage_Core_Model_Config_Element[] $resources */
        $resources = $resourcesNode->children();
        foreach ($resources as $name => $resource) {
            if (!$resource->setup) {
                continue;
            }

            $className = 'Mage_Core_Model_Resource_Setup';
            if (isset($resource->setup->class)) {
                $className = $resource->setup->getClassName();
            }

            /** @var Mage_Core_Model_Resource_Resource $setupResourcesClass */
            $setupResourcesClass    = new $className($name);
            $setupResources[$name]  = $setupResourcesClass;
        }

        return $setupResources;
    }

    protected function _getResource(): Mage_Core_Model_Resource_Resource
    {
        /** @var Mage_Core_Model_Resource_Resource $model */
        $model = Mage::getResourceSingleton('core/resource');
        return $model;
    }

    /**
     * @throws ReflectionException
     */
    protected function _getAvaiableDbFilesFromResource(Mage_Core_Model_Resource_Setup $mageCoreModelResourceSetup, array $args = []): array
    {
        $result = (array) $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $mageCoreModelResourceSetup, $args);

        //an installation runs the installation script first, then any upgrades
        if ($args[0] == Mage_Core_Model_Resource_Setup::TYPE_DB_INSTALL) {
            $args[0] = Mage_Core_Model_Resource_Setup::TYPE_DB_UPGRADE;
            $args[1] = $result[0]['toVersion'];
            $result = array_merge(
                $result,
                (array) $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $mageCoreModelResourceSetup, $args),
            );
        }

        return $result;
    }

    /**
     * @throws ReflectionException
     */
    protected function _getAvaiableDataFilesFromResource(Mage_Core_Model_Resource_Setup $mageCoreModelResourceSetup, array $args = []): array
    {
        $result = (array) $this->_callProtectedMethodFromObject('_getAvailableDataFiles', $mageCoreModelResourceSetup, $args);
        if ($args[0] == Mage_Core_Model_Resource_Setup::TYPE_DATA_INSTALL) {
            $args[0] = Mage_Core_Model_Resource_Setup::TYPE_DATA_UPGRADE;
            $args[1] = $result[0]['toVersion'];
            $result = array_merge(
                $result,
                (array) $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $mageCoreModelResourceSetup, $args),
            );
        }

        return $result;
    }

    /**
     * @return array|string
     * @throws ReflectionException
     */
    protected function _callProtectedMethodFromObject(string $method, object $object, array $args = [])
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $args);
    }

    /**
     * @param mixed $value
     * @throws ReflectionException
     */
    protected function _setProtectedPropertyFromObjectToValue(string $property, object $object, $value): void
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    protected function _getProtectedPropertyFromObject(string $property, object $object)
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * @return string|bool
     */
    protected function _getDbVersionFromName(string $name)
    {
        return $this->_getResource()->getDbVersion($name);
    }

    /**
     * @return string|bool
     */
    protected function _getDbDataVersionFromName(string $name)
    {
        return $this->_getResource()->getDataVersion($name);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    protected function _getConfiguredVersionFromResourceObject(object $object)
    {
        $moduleConfig = $this->_getProtectedPropertyFromObject('_moduleConfig', $object);

        return $moduleConfig->version;
    }

    /**
     * @throws ReflectionException
     */
    protected function _getAllSetupResourceObjectThatNeedUpdates(?array $setupResources = null): array
    {
        $setupResources = $setupResources !== null && $setupResources !== [] ? $setupResources : $this->_getAllSetupResourceObjects();
        $needsUpdate = [];
        foreach ($setupResources as $name => $setupResource) {
            $db_ver = $this->_getDbVersionFromName($name);
            $db_data_ver = $this->_getDbDataVersionFromName($name);
            $config_ver = $this->_getConfiguredVersionFromResourceObject($setupResource);

            if ((string) $config_ver === (string) $db_ver && //structure
                (string) $config_ver === (string) $db_data_ver //data
            ) {
                continue;
            }

            $needsUpdate[$name] = $setupResource;
        }

        return $needsUpdate;
    }

    protected function _log(string $message): void
    {
        $this->_output->writeln($message);
    }

    protected function _setOutput(OutputInterface $output): void
    {
        $this->_output = $output;
    }

    protected function _setInput(InputInterface $input): void
    {
        $this->_input = $input;
    }

    /**
     * @throws ReflectionException
     */
    protected function _outputUpdateInformation(array $needsUpdate): void
    {
        $output = $this->_output;
        foreach ($needsUpdate as $name => $setupResource) {
            $dbVersion = $this->_getDbVersionFromName($name);
            $dbDataVersion = $this->_getDbDataVersionFromName($name);
            $configVersion = $this->_getConfiguredVersionFromResourceObject($setupResource);

            $moduleConfig = $this->_getProtectedPropertyFromObject('_moduleConfig', $setupResource);
            $output->writeln(
                [
                    '+--------------------------------------------------+',
                    'Resource Name:             ' . $name,
                    'For Module:                ' . $moduleConfig->getName(),
                    'Class:                     ' . get_class($setupResource),
                    'Current Structure Version: ' . $dbVersion,
                    'Current Data Version:      ' . $dbDataVersion,
                    'Configured Version:        ' . $configVersion,
                ],
            );

            $args = ['', (string) $dbVersion, (string) $configVersion];

            $args[0] = $dbVersion
                ? Mage_Core_Model_Resource_Setup::TYPE_DB_UPGRADE
                : Mage_Core_Model_Resource_Setup::TYPE_DB_INSTALL;
            $output->writeln('Structure Files to Run: ');
            $filesStructure = $this->_getAvaiableDbFilesFromResource($setupResource, $args);
            $this->_outputFileArray($filesStructure);
            $output->writeln('');

            $args[0] = $dbVersion
                ? Mage_Core_Model_Resource_Setup::TYPE_DATA_UPGRADE
                : Mage_Core_Model_Resource_Setup::TYPE_DATA_INSTALL;
            $output->writeln('Data Files to Run: ');
            $filesData = $this->_getAvaiableDataFilesFromResource($setupResource, $args);
            $this->_outputFileArray($filesData);
            $output->writeln('+--------------------------------------------------+');
            $output->writeln('');
        }
    }

    protected function _outputFileArray(array $files): void
    {
        $output = $this->_output;
        if (count($files) == 0) {
            $output->writeln('No files found');

            return;
        }

        foreach ($files as $file) {
            $output->writeln(str_replace(Mage::getBaseDir() . '/', '', $file['fileName']));
        }
    }

    /**
     * Runs a single named setup resource
     *
     * This method nukes the global/resources node in the global config
     * and then repopulates it with **only** the $name resource. Then it
     * calls the standard Magento `applyAllUpdates` method.
     *
     * The benefit of this approach is we don't need to recreate the entire
     * setup resource running logic ourselves.  Yay for code reuse
     *
     * The downside is we should probably exit quickly, as anything else that
     * uses the global/resources node is going to behave weird.
     *
     * @throws RuntimeException
     * @todo     Repopulate global config after running?  Non trivial since setNode escapes strings
     */
    protected function _runNamedSetupResource(string $name, array $needsUpdate, string $type): void
    {
        $output = $this->_output;
        if (!in_array($type, [self::TYPE_MIGRATION_STRUCTURE, self::TYPE_MIGRATION_DATA])) {
            throw new RuntimeException('Invalid Type [' . $type . ']: structure, data is valid');
        }

        if (!array_key_exists($name, $needsUpdate)) {
            $output->writeln('<error>No updates to run for ' . $name . ', skipping </error>');

            return;
        }

        //remove all other setup resources from configuration
        //(in memory, do not persist this to cache)
        $realConfig = Mage::getConfig();
        $resources = $realConfig->getNode('global/resources');
        if ($resources) {
            foreach ($resources->children() as $resource) {
                if (!$resource->setup) {
                    continue;
                }

                unset($resource->setup);
            }
        }

        //recreate our specific node in <global><resources></resource></global>
        //allows for theoretical multiple runs
        /** @var Varien_Simplexml_Element $setupResourceConfig */
        $setupResourceConfig    = $this->_secondConfig->getNode('global/resources/' . $name);
        $setupResourceSetup     = $setupResourceConfig->setup;
        $moduleName             = $setupResourceSetup->module;
        $className              = $setupResourceSetup->class;

        /** @var Varien_Simplexml_Element $specificResource */
        $specificResource = $realConfig->getNode('global/resources/' . $name);
        $setup = $specificResource->addChild('setup');
        if ($moduleName) {
            $setup->addChild('module', $moduleName->__toString());
        } else {
            $output->writeln(
                '<error>No module node configured for ' . $name . ', possible configuration error </error>',
            );
        }

        if ($className) {
            $setup->addChild('class', $className->__toString());
        }

        //and finally, RUN THE UPDATES
        try {
            ob_start();
            if ($type === self::TYPE_MIGRATION_STRUCTURE) {
                $this->_stashEventContext();
                Mage_Core_Model_Resource_Setup::applyAllUpdates();
                $this->_restoreEventContext();
            }

            if ($type === self::TYPE_MIGRATION_DATA) {
                Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
            }

            $exceptionOutput = (string) ob_get_clean();
            $this->_output->writeln($exceptionOutput);
        } catch (Exception $exception) {
            $exceptionOutput = (string) ob_get_clean();
            $this->_processExceptionDuringUpdate($exception, $name, $exceptionOutput);
            if ($this->_input->getOption('stop-on-error')) {
                throw new RuntimeException('Setup stopped with errors', $exception->getCode(), $exception);
            }
        }
    }

    protected function _processExceptionDuringUpdate(
        Exception $exception,
        string    $name,
        string    $magentoExceptionOutput
    ): void {
        $input = $this->_input;
        $output = $this->_output;
        $output->writeln(['<error>Magento encountered an error while running the following setup resource.</error>', '', sprintf('    %s ', $name), '', '<error>The Good News:</error> You know the error happened, and the database', 'information below will  help you fix this error!', '', "<error>The Bad News:</error> Because Magento/MySQL can't run setup resources", 'transactionally your database is now in an half upgraded, invalid', 'state. Even if you fix the error, new errors may occur due to', 'this half upgraded, invalid state.', '', 'What to Do: ', '1. Figure out why the error happened, and manually fix your', "   database and/or system so it won't happen again.", '2. Restore your database from backup.', '3. Re-run the scripts.', '', 'Exception Message:', $exception->getMessage(), '']);

        if ($magentoExceptionOutput !== '' && $magentoExceptionOutput !== '0') {
            $dialog = $this->getQuestionHelper();
            $question = new Question('<question>Press Enter to view raw Magento error text:</question> ');
            $dialog->ask($input, $output, $question);

            $output->writeln('Magento Exception Error Text:');
            echo $magentoExceptionOutput, "\n"; //echoing (vs. writeln) to avoid seg fault
        }
    }

    protected function _checkCacheSettings(): bool
    {
        $output = $this->_output;
        $allTypes = Mage::app()->useCache();
        if ($allTypes && $allTypes['config'] !== '1') {
            $output->writeln('<error>ERROR: Config Cache is Disabled</error>');
            $output->writeln('This command will not run with the configuration cache disabled.');
            $output->writeln('Please change your Magento settings at System -> Cache Management');
            $output->writeln('');

            return false;
        }

        return true;
    }

    protected function _runStructureOrDataScripts(string $toUpdate, array $needsUpdate, string $type): void
    {
        $input = $this->_input;
        $output = $this->_output;
        $output->writeln('The next ' . $type . ' update to run is <info>' . $toUpdate . '</info>');

        $questionHelper = $this->getQuestionHelper();
        $question = new Question('<question>Press Enter to Run this update:</question> ');
        $questionHelper->ask($input, $output, $question);

        $start = microtime(true);
        $this->_runNamedSetupResource($toUpdate, $needsUpdate, $type);
        $time_ran = microtime(true) - $start;
        $output->writeln('');
        $output->writeln(ucwords($type) . ' update <info>' . $toUpdate . '</info> complete.');
        $output->writeln('Ran in ' . floor($time_ran * 1000) . 'ms');
    }

    protected function _getTestedVersions(): array
    {
        return $this->_config['tested-versions'];
    }

    /**
     * @throws ReflectionException
     */
    protected function _restoreEventContext(): void
    {
        $app = Mage::app();
        $this->_setProtectedPropertyFromObjectToValue('_events', $app, $this->_eventStash);
    }

    /**
     * @throws ReflectionException
     */
    protected function _stashEventContext(): void
    {
        $app = Mage::app();
        $events = $this->_getProtectedPropertyFromObject('_events', $app);
        $this->_eventStash = $events;
        $this->_setProtectedPropertyFromObjectToValue('_events', $app, []);
    }

    protected function _init(): bool
    {
        //bootstrap magento
        $this->detectMagento($this->_output);
        if (!$this->initMagento()) {
            return false;
        }

        //don't run if cache is off.  If cache is off that means
        //setup resource will run automagically
        if (!$this->_checkCacheSettings()) {
            return false;
        }

        //load a second, not cached, config.xml tree
        $this->_loadSecondConfig();

        return true;
    }

    /**
     * @throws ReflectionException
     */
    protected function _analyzeSetupResourceClasses(): array
    {
        $output = $this->_output;
        $this->writeSection($output, 'Analyzing Setup Resource Classes');
        $setupResources = $this->_getAllSetupResourceObjects();
        $needsUpdate = $this->_getAllSetupResourceObjectThatNeedUpdates($setupResources);

        $output->writeln(
            'Found <info>' . count($setupResources) . '</info> configured setup resource(s)</info>',
        );
        $output->writeln(
            'Found <info>' . count($needsUpdate) . '</info> setup resource(s) which need an update</info>',
        );

        return $needsUpdate;
    }

    /**
     * @throws ReflectionException
     */
    protected function _listDetailedUpdateInformation(array $needsUpdate): void
    {
        $input = $this->_input;
        $output = $this->_output;

        $questionHelper = $this->getQuestionHelper();
        $question = new Question('<question>Press Enter to View Update Information:</question> ');
        $questionHelper->ask($input, $output, $question);

        $this->writeSection($output, 'Detailed Update Information');
        $this->_outputUpdateInformation($needsUpdate);
    }

    protected function _runAllStructureUpdates(array $needsUpdate): void
    {
        $output = $this->_output;
        $this->writeSection($output, 'Run Structure Updates');
        $output->writeln('All structure updates run before data updates.');
        $output->writeln('');

        $c = 1;
        $total = count($needsUpdate);
        foreach (array_keys($needsUpdate) as $key) {
            $toUpdate = $key;
            $this->_runStructureOrDataScripts($toUpdate, $needsUpdate, self::TYPE_MIGRATION_STRUCTURE);
            $output->writeln(sprintf('(%d of %d)', $c, $total));
            $output->writeln('');
            ++$c;
        }

        $this->writeSection($output, 'Run Data Updates');
        $c = 1;
        $total = count($needsUpdate);
        foreach (array_keys($needsUpdate) as $key) {
            $toUpdate = $key;
            $this->_runStructureOrDataScripts($toUpdate, $needsUpdate, self::TYPE_MIGRATION_DATA);
            $output->writeln(sprintf('(%d of %d)', $c, $total));
            $output->writeln('');
            ++$c;
        }
    }
}
