<?php

namespace N98\Magento\Command\System\Setup;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class IncrementalCommand
 *
 * @package N98\Magento\Command\System\Setup
 * @codeCoverageIgnore
 */
class IncrementalCommand extends AbstractMagentoCommand
{
    const TYPE_MIGRATION_STRUCTURE = 'structure';
    const TYPE_MIGRATION_DATA = 'data';

    /**
     * @var OutputInterface
     */
    protected $_output;

    /**
     * @var InputInterface
     */
    protected $_input;

    /**
     * Holds our copy of the global config.
     *
     * Loaded to avoid grabbing the cached version, and so
     * we still have all our original information when we
     * destroy the real configuration
     *
     * @var mixed $_secondConfig
     */
    protected $_secondConfig;

    protected $_eventStash;

    /**
     * @var array
     */
    protected $_config;

    protected function configure()
    {
        $this
            ->setName('sys:setup:incremental')
            ->setDescription('List new setup scripts to run, then runs one script')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stops execution of script on error')
            ->setHelp(
                'Examines an un-cached configuration tree and determines which ' .
                'structure and data setup resource scripts need to run, and then runs them.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_config = $this->getCommandConfig();

        //sets output so we can access it from all methods
        $this->_setOutput($output);
        $this->_setInput($input);
        if (false === $this->_init()) {
            return;
        }
        $needsUpdate = $this->_analyzeSetupResourceClasses();

        if (count($needsUpdate) === 0) {
            return;
        }

        $this->_listDetailedUpdateInformation($needsUpdate);
        $this->_runAllStructureUpdates($needsUpdate);
        $output->writeln('We have run all the setup resource scripts.');
    }

    protected function _loadSecondConfig()
    {
        $config = new \Mage_Core_Model_Config;
        $config->loadBase(); //get app/etc
        $this->_secondConfig = \Mage::getConfig()->loadModulesConfiguration('config.xml', $config);
    }

    /**
     * @return array
     */
    protected function _getAllSetupResourceObjects()
    {
        $config = $this->_secondConfig;
        $resources = $config->getNode('global/resources')->children();
        $setupResources = array();
        foreach ($resources as $name => $resource) {
            if (!$resource->setup) {
                continue;
            }
            $className = 'Mage_Core_Model_Resource_Setup';
            if (isset($resource->setup->class)) {
                $className = $resource->setup->getClassName();
            }

            $setupResources[$name] = new $className($name);
        }

        return $setupResources;
    }

    /**
     * @return \Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return \Mage::getResourceSingleton('core/resource');
    }

    /**
     * @param \Mage_Core_Model_Resource_Setup $setupResource
     * @param array $args
     *
     * @return array|mixed
     */
    protected function _getAvaiableDbFilesFromResource($setupResource, $args = array())
    {
        $result = $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $setupResource, $args);

        //an install runs the install script first, then any upgrades
        if ($args[0] == \Mage_Core_Model_Resource_Setup::TYPE_DB_INSTALL) {
            $args[0] = \Mage_Core_Model_Resource_Setup::TYPE_DB_UPGRADE;
            $args[1] = $result[0]['toVersion'];
            $result = array_merge(
                $result,
                $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $setupResource, $args)
            );
        }

        return $result;
    }

    /**
     * @param \Mage_Core_Model_Resource_Setup $setupResource
     * @param array $args
     *
     * @return array|mixed
     */
    protected function _getAvaiableDataFilesFromResource($setupResource, $args = array())
    {
        $result = $this->_callProtectedMethodFromObject('_getAvailableDataFiles', $setupResource, $args);
        if ($args[0] == \Mage_Core_Model_Resource_Setup::TYPE_DATA_INSTALL) {
            $args[0] = \Mage_Core_Model_Resource_Setup::TYPE_DATA_UPGRADE;
            $args[1] = $result[0]['toVersion'];
            $result = array_merge(
                $result,
                $this->_callProtectedMethodFromObject('_getAvailableDbFiles', $setupResource, $args)
            );
        }

        return $result;
    }

    /**
     * @param string $method
     * @param object $object
     * @param array $args
     *
     * @return mixed
     */
    protected function _callProtectedMethodFromObject($method, $object, $args = array())
    {
        $r = new ReflectionClass($object);
        $m = $r->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($object, $args);
    }

    /**
     * @param string $property
     * @param object $object
     * @param mixed $value
     */
    protected function _setProtectedPropertyFromObjectToValue($property, $object, $value)
    {
        $r = new ReflectionClass($object);
        $p = $r->getProperty($property);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    /**
     * @param string $property
     * @param object $object
     *
     * @return mixed
     */
    protected function _getProtectedPropertyFromObject($property, $object)
    {
        $r = new ReflectionClass($object);
        $p = $r->getProperty($property);
        $p->setAccessible(true);

        return $p->getValue($object);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function _getDbVersionFromName($name)
    {
        return $this->_getResource()->getDbVersion($name);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function _getDbDataVersionFromName($name)
    {
        return $this->_getResource()->getDataVersion($name);
    }

    /**
     * @param Object $object
     *
     * @return mixed
     */
    protected function _getConfiguredVersionFromResourceObject($object)
    {
        $moduleConfig = $this->_getProtectedPropertyFromObject('_moduleConfig', $object);

        return $moduleConfig->version;
    }

    /**
     * @param bool|array $setupResources
     *
     * @return array
     */
    protected function _getAllSetupResourceObjectThatNeedUpdates($setupResources = false)
    {
        $setupResources = $setupResources ? $setupResources : $this->_getAllSetupResourceObjects();
        $needsUpdate = array();
        foreach ($setupResources as $name => $setupResource) {
            $db_ver = $this->_getDbVersionFromName($name);
            $db_data_ver = $this->_getDbDataVersionFromName($name);
            $config_ver = $this->_getConfiguredVersionFromResourceObject($setupResource);

            if (
                (string) $config_ver == (string) $db_ver && //structure
                (string) $config_ver == (string) $db_data_ver //data
            ) {
                continue;
            }
            $needsUpdate[$name] = $setupResource;
        }

        return $needsUpdate;
    }

    /**
     * @param string $message
     */
    protected function _log($message)
    {
        $this->_output->writeln($message);
    }

    /**
     * @param OutputInterface $output
     */
    protected function _setOutput(OutputInterface $output)
    {
        $this->_output = $output;
    }

    /**
     * @param InputInterface $input
     */
    protected function _setInput(InputInterface $input)
    {
        $this->_input = $input;
    }

    /**
     * @param array $needsUpdate
     */
    protected function _outputUpdateInformation(array $needsUpdate)
    {
        $output = $this->_output;
        foreach ($needsUpdate as $name => $setupResource) {
            $dbVersion = $this->_getDbVersionFromName($name);
            $dbDataVersion = $this->_getDbDataVersionFromName($name);
            $configVersion = $this->_getConfiguredVersionFromResourceObject($setupResource);

            $moduleConfig = $this->_getProtectedPropertyFromObject('_moduleConfig', $setupResource);
            $output->writeln(
                array(
                    '+--------------------------------------------------+',
                    'Resource Name:             ' . $name,
                    'For Module:                ' . $moduleConfig->getName(),
                    'Class:                     ' . get_class($setupResource),
                    'Current Structure Version: ' . $dbVersion,
                    'Current Data Version:      ' . $dbDataVersion,
                    'Configured Version:        ' . $configVersion,
                )
            );

            $args = array(
                '',
                (string) $dbVersion,
                (string) $configVersion,
            );

            $args[0] = $dbVersion
                ? \Mage_Core_Model_Resource_Setup::TYPE_DB_UPGRADE
                : \Mage_Core_Model_Resource_Setup::TYPE_DB_INSTALL;
            $output->writeln('Structure Files to Run: ');
            $filesStructure = $this->_getAvaiableDbFilesFromResource($setupResource, $args);
            $this->_outputFileArray($filesStructure, $output);
            $output->writeln("");

            $args[0] = $dbVersion
                ? \Mage_Core_Model_Resource_Setup::TYPE_DATA_UPGRADE
                : \Mage_Core_Model_Resource_Setup::TYPE_DATA_INSTALL;
            $output->writeln('Data Files to Run: ');
            $filesData = $this->_getAvaiableDataFilesFromResource($setupResource, $args);
            $this->_outputFileArray($filesData, $output);
            $output->writeln('+--------------------------------------------------+');
            $output->writeln('');
        }
    }

    /**
     * @param array $files
     */
    protected function _outputFileArray($files)
    {
        $output = $this->_output;
        if (count($files) == 0) {
            $output->writeln('No files found');

            return;
        }
        foreach ($files as $file) {
            $output->writeln(str_replace(\Mage::getBaseDir() . '/', '', $file['fileName']));
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
     * @todo     Repopulate global config after running?  Non trivial since setNode escapes strings
     *
     * @param string $name
     * @param array $needsUpdate
     * @param string $type
     *
     * @throws RuntimeException
     * @internal param $string
     */
    protected function _runNamedSetupResource($name, array $needsUpdate, $type)
    {
        $output = $this->_output;
        if (!in_array($type, array(self::TYPE_MIGRATION_STRUCTURE, self::TYPE_MIGRATION_DATA))) {
            throw new RuntimeException('Invalid Type [' . $type . ']: structure, data is valid');
        }

        if (!array_key_Exists($name, $needsUpdate)) {
            $output->writeln('<error>No updates to run for ' . $name . ', skipping </error>');

            return;
        }

        //remove all other setup resources from configuration
        //(in memory, do not persist this to cache)
        $realConfig = \Mage::getConfig();
        $resources = $realConfig->getNode('global/resources');
        foreach ($resources->children() as $resource) {
            if (!$resource->setup) {
                continue;
            }
            unset($resource->setup);
        }
        //recreate our specific node in <global><resources></resource></global>
        //allows for theoretical multiple runs
        $setupResourceConfig = $this->_secondConfig->getNode('global/resources/' . $name);
        $moduleName = $setupResourceConfig->setup->module;
        $className = $setupResourceConfig->setup->class;

        $specificResource = $realConfig->getNode('global/resources/' . $name);
        $setup = $specificResource->addChild('setup');
        if ($moduleName) {
            $setup->addChild('module', $moduleName);
        } else {
            $output->writeln(
                '<error>No module node configured for ' . $name . ', possible configuration error </error>'
            );
        }

        if ($className) {
            $setup->addChild('class', $className);
        }

        //and finally, RUN THE UPDATES
        try {
            ob_start();
            if ($type == self::TYPE_MIGRATION_STRUCTURE) {
                $this->_stashEventContext();
                \Mage_Core_Model_Resource_Setup::applyAllUpdates();
                $this->_restoreEventContext();
            } else {
                if ($type == self::TYPE_MIGRATION_DATA) {
                    \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
                }
            }
            $exceptionOutput = ob_get_clean();
            $this->_output->writeln($exceptionOutput);
        } catch (Exception $e) {
            $exceptionOutput = ob_get_clean();
            $this->_processExceptionDuringUpdate($e, $name, $exceptionOutput);
            if ($this->_input->getOption('stop-on-error')) {
                throw new RuntimeException('Setup stopped with errors');
            }
        }
    }

    /**
     * @param Exception $e
     * @param string $name
     * @param string $magentoExceptionOutput
     */
    protected function _processExceptionDuringUpdate(
        Exception $e,
        $name,
        $magentoExceptionOutput
    ) {
        $output = $this->_output;
        $output->writeln(array(
            "<error>Magento encountered an error while running the following setup resource.</error>",
            "",
            "    $name ",
            "",
            "<error>The Good News:</error> You know the error happened, and the database",
            "information below will  help you fix this error!",
            "",
            "<error>The Bad News:</error> Because Magento/MySQL can't run setup resources",
            "transactionally your database is now in an half upgraded, invalid",
            "state. Even if you fix the error, new errors may occur due to",
            "this half upgraded, invalid state.",
            '',
            "What to Do: ",
            "1. Figure out why the error happened, and manually fix your",
            "   database and/or system so it won't happen again.",
            "2. Restore your database from backup.",
            "3. Re-run the scripts.",
            "",
            "Exception Message:",
            $e->getMessage(),
            "",
        ));

        if ($magentoExceptionOutput) {
            /* @var  $dialog DialogHelper */
            $dialog = $this->getHelper('dialog');
            $dialog->ask(
                $output,
                '<question>Press Enter to view raw Magento error text:</question> '
            );
            $output->writeln("Magento Exception Error Text:");
            echo $magentoExceptionOutput, "\n"; //echoing (vs. writeln) to avoid seg fault
        }
    }

    /**
     * @return bool
     */
    protected function _checkCacheSettings()
    {
        $output = $this->_output;
        $allTypes = \Mage::app()->useCache();
        if ($allTypes['config'] !== '1') {
            $output->writeln('<error>ERROR: Config Cache is Disabled</error>');
            $output->writeln('This command will not run with the configuration cache disabled.');
            $output->writeln('Please change your Magento settings at System -> Cache Management');
            $output->writeln('');

            return false;
        }

        return true;
    }

    /**
     * @param string $toUpdate
     * @param array $needsUpdate
     * @param string $type
     */
    protected function _runStructureOrDataScripts($toUpdate, array $needsUpdate, $type)
    {
        $output = $this->_output;
        $output->writeln('The next ' . $type . ' update to run is <info>' . $toUpdate . '</info>');
        /* @var  $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');
        $dialog->ask(
            $output,
            '<question>Press Enter to Run this update: </question>'
        );

        $start = microtime(true);
        $this->_runNamedSetupResource($toUpdate, $needsUpdate, $type);
        $time_ran = microtime(true) - $start;
        $output->writeln('');
        $output->writeln(ucwords($type) . ' update <info>' . $toUpdate . '</info> complete.');
        $output->writeln('Ran in ' . floor($time_ran * 1000) . 'ms');
    }

    /**
     * @return array
     */
    protected function _getTestedVersions()
    {
        return $this->_config['tested-versions'];
    }

    protected function _restoreEventContext()
    {
        $app = \Mage::app();
        $this->_setProtectedPropertyFromObjectToValue('_events', $app, $this->_eventStash);
    }

    protected function _stashEventContext()
    {
        $app = \Mage::app();
        $events = $this->_getProtectedPropertyFromObject('_events', $app);
        $this->_eventStash = $events;
        $this->_setProtectedPropertyFromObjectToValue('_events', $app, array());
    }

    /**
     * @return bool
     */
    protected function _init()
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
     * @return array
     */
    protected function _analyzeSetupResourceClasses()
    {
        $output = $this->_output;
        $this->writeSection($output, 'Analyzing Setup Resource Classes');
        $setupResources = $this->_getAllSetupResourceObjects();
        $needsUpdate = $this->_getAllSetupResourceObjectThatNeedUpdates($setupResources);

        $output->writeln(
            'Found <info>' . count($setupResources) . '</info> configured setup resource(s)</info>'
        );
        $output->writeln(
            'Found <info>' . count($needsUpdate) . '</info> setup resource(s) which need an update</info>'
        );

        return $needsUpdate;
    }

    /**
     * @param array $needsUpdate
     */
    protected function _listDetailedUpdateInformation(array $needsUpdate)
    {
        $output = $this->_output;
        /* @var  $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');
        $dialog->ask(
            $output,
            '<question>Press Enter to View Update Information: </question>'
        );

        $this->writeSection($output, 'Detailed Update Information');
        $this->_outputUpdateInformation($needsUpdate, $output);
    }

    /**
     * @param array $needsUpdate
     */
    protected function _runAllStructureUpdates(array $needsUpdate)
    {
        $output = $this->_output;
        $this->writeSection($output, "Run Structure Updates");
        $output->writeln('All structure updates run before data updates.');
        $output->writeln('');

        $c = 1;
        $total = count($needsUpdate);
        foreach ($needsUpdate as $key => $value) {
            $toUpdate = $key;
            $this->_runStructureOrDataScripts($toUpdate, $needsUpdate, self::TYPE_MIGRATION_STRUCTURE);
            $output->writeln("($c of $total)");
            $output->writeln('');
            $c++;
        }

        $this->writeSection($output, "Run Data Updates");
        $c = 1;
        $total = count($needsUpdate);
        foreach ($needsUpdate as $key => $value) {
            $toUpdate = $key;
            $this->_runStructureOrDataScripts($toUpdate, $needsUpdate, self::TYPE_MIGRATION_DATA);
            $output->writeln("($c of $total)");
            $output->writeln('');
            $c++;
        }
    }
}
