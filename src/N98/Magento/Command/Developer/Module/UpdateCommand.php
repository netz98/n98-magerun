<?php

namespace N98\Magento\Command\Developer\Module;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Update a magento module
 */
class UpdateCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     */
    protected $baseFolder;

    /**
     * @var string
     */
    protected $moduleDirectory;

    /**
     * @var string
     */
    protected $vendorNamespace;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var string
     */
    protected $codePool;

    /**
     * @var array
     */
    protected $configNodes = array();

    /**
     * @var bool
     */
    protected $testMode = false;

    /**
     * @param boolean $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * @return boolean
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    protected function configure()
    {
        $this
            ->setName('dev:module:update')
            ->addArgument('vendorNamespace', InputArgument::REQUIRED, 'Namespace (your company prefix)')
            ->addArgument('moduleName', InputArgument::REQUIRED, 'Name of your module.')
            ->addOption('set-version', null, InputOption::VALUE_NONE, 'Set module version in config.xml')
            ->addOption('add-blocks', null, InputOption::VALUE_NONE, 'Adds blocks class to config.xml')
            ->addOption('add-helpers', null, InputOption::VALUE_NONE, 'Adds helpers class to config.xml')
            ->addOption('add-models', null, InputOption::VALUE_NONE, 'Adds models class to config.xml')
            ->addOption(
                'add-all',
                null,
                InputOption::VALUE_NONE,
                'Adds blocks, helpers and models classes to config.xml'
            )
            ->addOption(
                'add-resource-model',
                null,
                InputOption::VALUE_NONE,
                'Adds resource model class and entities to config.xml'
            )
            ->addOption(
                'add-routers',
                null,
                InputOption::VALUE_NONE,
                'Adds routers for frontend or admin areas to config.xml'
            )
            ->addOption(
                'add-events',
                null,
                InputOption::VALUE_NONE,
                'Adds events observer to global, frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                'add-layout-updates',
                null,
                InputOption::VALUE_NONE,
                'Adds layout updates to frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                'add-translate',
                null,
                InputOption::VALUE_NONE,
                'Adds translate configuration to frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                'add-default',
                null,
                InputOption::VALUE_NONE,
                'Adds default value (related to system.xml groups/fields)'
            )
            ->setDescription('Update a Magento module.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initMagento();
        $this->initArguments($input);

        if ($this->hasAddResourceModelOption($input)) {
            $this->askResourceModelOptions($output);
        }

        if ($this->hasAddRoutersOption($input)) {
            $this->askRoutersOptions($output);
        }

        if ($this->hasAddEventsOption($input)) {
            $this->askEventsOptions($output);
        }

        if ($this->hasAddLayoutUpdatesOptions($input)) {
            $this->askLayoutUpdatesOptions($output);
        }

        if ($this->hasAddTranslateOption($input)) {
            $this->askTranslateOptions($output);
        }

        if ($this->hasAddDefaultOption($input)) {
            $this->askDefaultOptions($output);
        }

        $this->setModuleDirectory($this->getModuleDir());
        $this->writeModuleConfig($input, $output);
    }

    /**
     * @param InputInterface $input
     */
    protected function initArguments(InputInterface $input)
    {
        $this->vendorNamespace = ucfirst($input->getArgument('vendorNamespace'));
        $this->moduleName = ucfirst($input->getArgument('moduleName'));
        $this->determineModuleCodePool();
    }

    /**
     * Find module codepool from module directory
     *
     * @return string
     */
    protected function determineModuleCodePool()
    {
        if ($this->testMode === true) {
            $this->codePool = 'local';
            $this->_magentoRootFolder = './' . $this->getModuleNamespace() . '/src';
            $this->moduleDirectory = $this->_magentoRootFolder
                . '/app/code/'
                . $this->codePool
                . '/' . $this->vendorNamespace
                . '/' . $this->moduleName;
        } else {
            $this->moduleDirectory = $this->getModuleDir();
        }

        if (preg_match('/community/', $this->moduleDirectory)) {
            $this->codePool = 'community';
        }

        if (preg_match('/local/', $this->moduleDirectory)) {
            $this->codePool = 'local';
        }

        return $this->codePool;
    }

    /**
     * @param string $moduleDir
     * @throws RuntimeException
     */
    protected function setModuleDirectory($moduleDir)
    {
        if (!file_exists($moduleDir)) {
            throw new RuntimeException(
                'Module does not exist. Use dev:module:create to create it before updating. Stop.'
            );
        }

        $this->moduleDirectory = $moduleDir;
    }

    /**
     * @return DialogHelper
     */
    protected function getDialog()
    {
        return $this->getHelper('dialog');
    }

    /**
     * Writes module config file for given options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function writeModuleConfig(InputInterface $input, OutputInterface $output)
    {
        $configXml = $this->getConfigXml();

        $this->setVersion($input, $output, $configXml);
        $this->setGlobalNode($input, $output, $configXml);
        $this->setResourceModelNode($input, $configXml);
        $this->setRoutersNode($input, $configXml);
        $this->setEventsNode($input, $configXml);
        $this->setLayoutUpdatesNode($input, $configXml);
        $this->setTranslateNode($input, $configXml);
        $this->setDefaultNode($input, $configXml);
        $this->putConfigXml($configXml);

        $output->writeln('<info>Edited file: <comment>' . $this->getOutFile() . '<comment></info>');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param SimpleXMLElement $configXml
     */
    protected function setVersion(InputInterface $input, OutputInterface $output, \SimpleXMLElement $configXml)
    {
        if ($this->shouldSetVersion($input)) {
            $modulesNode = $configXml->modules->{$this->getModuleNamespace()};
            $dialog = $this->getDialog();
            $version = trim($dialog->ask($output, '<question>Enter version number:</question>'));
            $modulesNode->version = $version;
        }
    }

    /**
     * Sets global xml config node
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param SimpleXMLElement $configXml
     */
    protected function setGlobalNode(InputInterface $input, OutputInterface $output, SimpleXMLElement $configXml)
    {
        if ($this->shouldAddAll($input)) {
            $this->addGlobalNode($configXml, 'blocks', '_Block');
            $this->addGlobalNode($configXml, 'helpers', '_Helper');
            $this->addGlobalNode($configXml, 'models', '_Model');
            $this->addResourceModelNodeIfConfirmed($output, $configXml);
        } else {
            if ($this->shouldAddBlocks($input)) {
                $this->addGlobalNode($configXml, 'blocks', '_Block');
            }

            if ($this->shouldAddHelpers($input)) {
                $this->addGlobalNode($configXml, 'helpers', '_Helper');
            }

            if ($this->shouldAddModels($input)) {
                $this->addGlobalNode($configXml, 'models', '_Model');
                $this->addResourceModelNodeIfConfirmed($output, $configXml);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param SimpleXMLElement $configXml
     */
    protected function addResourceModelNodeIfConfirmed(OutputInterface $output, \SimpleXMLElement $configXml)
    {
        $dialog = $this->getDialog();
        if ($dialog->askConfirmation(
            $output,
            '<question>Would you like to also add a Resource Model(y/n)?</question>',
            false
        )
        ) {
            $resourceModel = trim($dialog->ask($output, '<question>Resource Model:</question>'));
            $configXml->global->models
                ->{$this->getLowercaseModuleNamespace()}->addChild('resourceModel', $resourceModel);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setResourceModelNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddResourceModelOption($input)) {
            $this->addResourceModel($configXml);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setRoutersNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddRoutersOption($input)) {
            $this->addRouter($configXml, $this->configNodes['router_area']);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setEventsNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddEventsOption($input)) {
            $this->addEvent($configXml, $this->configNodes['events_area'], $this->configNodes['event_name']);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setLayoutUpdatesNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddLayoutUpdatesOptions($input)) {
            $this->addLayoutUpdate(
                $configXml,
                $this->configNodes['layout_updates_area'],
                $this->configNodes['layout_update_module']
            );
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setTranslateNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddTranslateOption($input)) {
            $this->addTranslate(
                $configXml,
                $this->configNodes['translate_area'],
                $this->configNodes['translate_module']
            );
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setDefaultNode(InputInterface $input, \SimpleXMLElement $configXml)
    {
        if ($this->hasAddDefaultOption($input)) {
            $this->addDefault($configXml);
        }
    }

    /**
     * Gets config XML
     *
     * @return SimpleXMLElement
     */
    protected function getConfigXml()
    {
        $currentConfigXml = $this->getCurrentConfigContent();
        $simpleXml = new \SimpleXMLElement($currentConfigXml);

        return $simpleXml;
    }

    /**
     * Returns current content of /etc/config.xml
     *
     * @return string
     */
    protected function getCurrentConfigContent()
    {
        $configFile = $this->getModuleDir() . '/etc/config.xml';

        return file_get_contents($configFile);
    }

    /**
     * @return string
     */
    protected function getModuleDir()
    {
        return isset($this->moduleDirectory)
            ? $this->moduleDirectory
            : \Mage::getModuleDir(false, $this->getModuleNamespace());
    }

    /**
     * Initiates resource nodes specific values
     */
    protected function initResourceModelConfigNodes()
    {
        $this->configNodes['resource_node_name'] = $this->getLowercaseModuleNamespace() . '_resource';
        $this->configNodes['resource_model_class'] = $this->getModuleNamespace() . '_Model_Resource';
        $this->configNodes['resource_deprecated_mysql4_node'] = false;
        $this->configNodes['resource_entities'] = array();
    }

    /**
     * Initiates routers config nodes specific values
     */
    protected function initRoutersConfigNodes()
    {
        $this->configNodes['router_area'] = false;
        $this->configNodes['use'] = false;
        $this->configNodes['frontname'] = false;
    }

    /**
     * Initiates events config nodes specific values
     */
    protected function initEventsConfigNodes()
    {
        $this->configNodes['events_area'] = false;
        $this->configNodes['event_name'] = false;
        $this->configNodes['event_observer'] = false;
        $this->configNodes['event_observer_class'] = false;
        $this->configNodes['event_observer_method'] = false;
    }

    /**
     * Initiates layout updates nodes specific values
     */
    protected function initLayoutUpdatesConfigNodes()
    {
        $this->configNodes['layout_updates_area'] = false;
        $this->configNodes['layout_update_module'] = false;
        $this->configNodes['layout_update_file'] = false;
    }

    /**
     * Initiates layout updates nodes specific values
     */
    protected function initTranslateConfigNodes()
    {
        $this->configNodes['translate_area'] = false;
        $this->configNodes['translate_module'] = $this->getModuleNamespace();
        $this->configNodes['translate_files_default'] = false;
    }

    /**
     * Initiates resource nodes specific values
     */
    protected function initDefaultConfigNodes()
    {
        $this->configNodes['default_section_name'] = false;
        $this->configNodes['default_group_name'] = false;
        $this->configNodes['default_field_name'] = false;
        $this->configNodes['default_field_value'] = false;
    }

    /**
     * Asks for routers node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askResourceModelOptions(OutputInterface $output)
    {
        $this->initResourceModelConfigNodes();
        $dialog = $this->getDialog();

        if ($dialog->askConfirmation($output,
            '<question>Would you like to set mysql4 deprecated node(y/n)?</question>',
            false
        )
        ) {
            $this->configNodes['resource_deprecated_mysql4_node'] = true;
        }

        $entityName = true;

        while ($entityName) {
            $entityName = trim($dialog->ask($output, '<question>Entity Name (leave blank to exit):</question>'));
            if (!$entityName) {
                break;
            }
            $entityTable = trim($dialog->ask($output, '<question>Entity Table:</question>'));
            $this->configNodes['resource_entities'][$entityName] = $entityTable;
        }
    }

    /**
     * Asks for routers node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askRoutersOptions(OutputInterface $output)
    {
        $this->initRoutersConfigNodes();
        $dialog = $this->getDialog();
        $area = trim($dialog->ask($output, '<question>Area (frontend|admin):</question>'));
        $use = trim($dialog->ask($output, '<question>Use:</question>'));
        $frontName = trim($dialog->ask($output, '<question>Frontname:</question>'));

        if ($area != 'frontend' && $area != 'admin') {
            throw new RuntimeException('Router area must be either "frontend" or "admin"');
        }

        $this->configNodes['router_area'] = $area;
        $this->configNodes['use'] = $use;
        $this->configNodes['frontname'] = $frontName;
    }

    /**
     * Asks for events node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askEventsOptions(OutputInterface $output)
    {
        $this->initEventsConfigNodes();
        $dialog = $this->getDialog();
        $area = trim($dialog->ask($output, '<question>Area (global|frontend|adminhtml):</question>'));
        $event = trim($dialog->ask($output, '<question>Event:</question>'));
        $observer = trim($dialog->ask($output, '<question>Event Observer:</question>'));
        $observerClass = trim($dialog->ask($output, '<question>Event Observer Class:</question>'));
        $observerMethod = trim($dialog->ask($output, '<question>Event Observer Method:</question>'));

        if ($area != 'global' && $area != 'frontend' && $area != 'adminhtml') {
            throw new RuntimeException('Event area must be either "global", "frontend" or "adminhtml"');
        }

        $this->configNodes['events_area'] = $area;
        $this->configNodes['event_name'] = $event;
        $this->configNodes['event_observer'] = $observer;
        $this->configNodes['event_observer_class'] = $observerClass;
        $this->configNodes['event_observer_method'] = $observerMethod;
    }

    /**
     * Asks for layout updates node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askLayoutUpdatesOptions(OutputInterface $output)
    {
        $this->initLayoutUpdatesConfigNodes();
        $dialog = $this->getDialog();
        $area = trim($dialog->ask($output, '<question>Area (frontend|adminhtml):</question>'));
        $module = trim($dialog->ask($output, '<question>Module:</question>'));
        $file = trim($dialog->ask($output, '<question>File:</question>'));

        if ($area != 'frontend' && $area != 'adminhtml') {
            throw new RuntimeException('Layout updates area must be either "frontend" or "adminhtml"');
        }

        $this->configNodes['layout_updates_area'] = $area;
        $this->configNodes['layout_update_module'] = $module;
        $this->configNodes['layout_update_file'] = $file;
    }

    /**
     * Asks for translate node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askTranslateOptions(OutputInterface $output)
    {
        $this->initTranslateConfigNodes();
        $dialog = $this->getDialog();
        $area = trim($dialog->ask($output, '<question>Area (frontend|adminhtml):</question>'));
        $file = trim($dialog->ask($output, '<question>File:</question>'));

        if ($area != 'frontend' && $area != 'adminhtml') {
            throw new RuntimeException('Layout updates area must be either "frontend" or "adminhtml"');
        }

        $this->configNodes['translate_area'] = $area;
        $this->configNodes['translate_files_default'] = $file;
    }

    /**
     * Asks for default node options
     *
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askDefaultOptions(OutputInterface $output)
    {
        $this->initDefaultConfigNodes();
        $dialog = $this->getDialog();
        $sectionName = strtolower(trim($dialog->ask($output, '<question>Section Name (lowercase):</question>')));
        $groupName = strtolower(trim($dialog->ask($output, '<question>Group Name (lowercase):</question>')));
        $fieldName = strtolower(trim($dialog->ask($output, '<question>Field Name:</question>')));
        $fieldValue = strtolower(trim($dialog->ask($output, '<question>Field Value:</question>')));

        $this->configNodes['default_section_name'] = $sectionName;
        $this->configNodes['default_group_name'] = $groupName;
        $this->configNodes['default_field_name'] = $fieldName;
        $this->configNodes['default_field_value'] = $fieldValue;
    }

    /**
     * @param SimpleXMLElement $configXml
     * @param string $type e.g. "blocks"
     * @param string $classSuffix e.g. "_Block"
     */
    protected function addGlobalNode(\SimpleXMLElement $configXml, $type, $classSuffix)
    {
        $this->removeChildNodeIfNotNull($configXml->global, $type);
        $global = $configXml->global ? $configXml->global : $configXml->addChild('global');
        $globalNode = $global->addChild($type);
        $moduleNamespaceNode = $globalNode->addChild($this->getLowercaseModuleNamespace());
        $moduleNamespaceNode->addChild('class', $this->getModuleNamespace() . $classSuffix);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     */
    protected function addResourceModel(\SimpleXMLElement $simpleXml)
    {
        if (is_null($simpleXml->global->models)) {
            throw new RuntimeException(
                'Global models node is not set. Run --add-models before --add-resource-model command.'
            );
        }

        $resourceNamespace = $this->getLowercaseModuleNamespace() . '_resource';
        $resourceModelNode = $simpleXml->global->models->$resourceNamespace ?
            $simpleXml->global->models->$resourceNamespace : $simpleXml->global->models->addChild($resourceNamespace);

        $simpleXml->global->models->$resourceNamespace->class
            ? null : $resourceModelNode->addChild('class', $this->configNodes['resource_model_class']);

        if ($this->configNodes['resource_deprecated_mysql4_node'] === true) {
            $simpleXml->global->models->$resourceNamespace->deprecatedNode ? null : $resourceModelNode->addChild(
                'deprecatedNode',
                $resourceNamespace . '_eav_mysql4'
            );
        } else {
            $this->removeChildNodeIfNotNull($resourceModelNode, 'deprecatedNode');
        }

        $entitiesNode = $resourceModelNode->entities
            ? $resourceModelNode->entities : $resourceModelNode->addChild('entities');

        foreach ($this->configNodes['resource_entities'] as $entity => $table) {
            $this->removeChildNodeIfNotNull($entitiesNode, $entity);
            $entityNode = $entitiesNode->addChild($entity);
            $entityNode->addChild('table', $table);
        }
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param $area
     */
    protected function addRouter(\SimpleXMLElement $simpleXml, $area)
    {
        $this->removeChildNodeIfNotNull($simpleXml->{$area}, 'routers');
        $areaNode = $simpleXml->{$area} ? $simpleXml->{$area} : $simpleXml->addChild($area);
        $routers = $areaNode->addChild('routers');
        $moduleNamespace = $routers->addChild($this->getLowercaseModuleNamespace());
        $moduleNamespace->addChild('use', $this->configNodes['use']);
        $args = $moduleNamespace->addChild('args');
        $args->addChild('module', $this->getLowercaseModuleNamespace());
        $args->addChild('frontName', $this->configNodes['frontname']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param $area
     * @param $event
     */
    protected function addEvent(\SimpleXMLElement $simpleXml, $area, $event)
    {
        $areaNode = $simpleXml->{$area} ? $simpleXml->{$area} : $simpleXml->addChild($area);
        $eventsNode = $areaNode->events ? $areaNode->events : $areaNode->addChild('events');
        $this->removeChildNodeIfNotNull($eventsNode, $event);
        $eventNode = $eventsNode->addChild($event);
        $observersNode = $eventNode->addChild('observers');
        $eventObserverNode = $observersNode->addChild($this->configNodes['event_observer']);
        $eventObserverNode->addChild('class', $this->configNodes['event_observer_class']);
        $eventObserverNode->addChild('method', $this->configNodes['event_observer_method']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param $area
     * @param $module
     */
    protected function addLayoutUpdate(\SimpleXMLElement $simpleXml, $area, $module)
    {
        $areaNode = $simpleXml->{$area} ? $simpleXml->{$area} : $simpleXml->addChild($area);
        $layoutNode = $areaNode->layout ? $areaNode->layout : $areaNode->addChild('layout');
        $updatesNode = $layoutNode->updates ? $layoutNode->updates : $layoutNode->addChild('updates');
        $this->removeChildNodeIfNotNull($updatesNode, $module);
        $moduleNode = $updatesNode->addChild($module);
        $moduleNode->addChild('file', $this->configNodes['layout_update_file']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param $area
     * @param $module
     */
    protected function addTranslate(\SimpleXMLElement $simpleXml, $area, $module)
    {
        $areaNode = $simpleXml->{$area} ? $simpleXml->{$area} : $simpleXml->addChild($area);
        $translateNode = $areaNode->translate ? $areaNode->translate : $areaNode->addChild('translate');
        $modulesNode = $translateNode->modules ? $translateNode->modules : $translateNode->addChild('modules');
        $this->removeChildNodeIfNotNull($modulesNode, $module);
        $moduleNode = $modulesNode->addChild($this->configNodes['translate_module']);
        $filesNode = $moduleNode->addChild('files');
        $filesNode->addChild('default', $this->configNodes['translate_files_default']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     */
    protected function addDefault(\SimpleXMLElement $simpleXml)
    {
        $defaultNode = $simpleXml->default ? $simpleXml->default : $simpleXml->addChild('default');
        $sectionNode = $defaultNode->{$this->configNodes['default_section_name']}
            ? $defaultNode->{$this->configNodes['default_section_name']}
            : $defaultNode->addChild($this->configNodes['default_section_name']);
        $groupNode = $sectionNode->{$this->configNodes['default_group_name']}
            ? $sectionNode->{$this->configNodes['default_group_name']}
            : $sectionNode->addChild($this->configNodes['default_group_name']);
        $this->removeChildNodeIfNotNull($groupNode, $this->configNodes['default_field_name']);
        $groupNode->addChild($this->configNodes['default_field_name'], $this->configNodes['default_field_value']);
    }

    /**
     * @return string
     */
    protected function getOutFile()
    {
        return $this->moduleDirectory . '/etc/config.xml';
    }

    /**
     * @param SimpleXMLElement $configXml
     */
    protected function putConfigXml(SimpleXMLElement $configXml)
    {
        $outFile = $this->getOutFile();

        $xml = $configXml->asXML();
        if (false === $xml) {
            throw new RuntimeException(sprintf('Failed to get XML from config SimpleXMLElement'));
        }

        file_put_contents($outFile, $this->asPrettyXml($xml));
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddResourceModelOption(InputInterface $input)
    {
        return $input->getOption('add-resource-model');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddRoutersOption(InputInterface $input)
    {
        return $input->getOption('add-routers');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddEventsOption(InputInterface $input)
    {
        return $input->getOption('add-events');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddLayoutUpdatesOptions(InputInterface $input)
    {
        return $input->getOption('add-layout-updates');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddTranslateOption(InputInterface $input)
    {
        return $input->getOption('add-translate');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddDefaultOption(InputInterface $input)
    {
        return $input->getOption('add-default');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldSetVersion(InputInterface $input)
    {
        return $input->getOption('set-version');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddBlocks(InputInterface $input)
    {
        return $input->getOption('add-blocks');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddHelpers(InputInterface $input)
    {
        return $input->getOption('add-helpers');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddModels(InputInterface $input)
    {
        return $input->getOption('add-models');
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddAll(InputInterface $input)
    {
        return $input->getOption('add-all');
    }

    /**
     * Gets module namespace e.g. Company_Modulename
     *
     * @return string
     */
    protected function getModuleNamespace()
    {
        return $this->vendorNamespace . '_' . $this->moduleName;
    }

    /**
     * @return string
     */
    protected function getLowercaseModuleNamespace()
    {
        return strtolower($this->vendorNamespace . '_' . $this->moduleName);
    }

    /**
     * @return string
     */
    protected function getLowercaseModuleName()
    {
        return strtolower($this->moduleName);
    }

    /**
     * Removes a child node if not null.
     * Deals with duplications of nodes when already in config
     *
     * @param $node
     * @param $child
     */
    protected function removeChildNodeIfNotNull($node, $child)
    {
        if (!is_null($node->{$child})) {
            unset($node->{$child});
        }
    }

    /**
     * Formats given string as pretty xml
     *
     * @param string $string
     *
     * @return string
     */
    protected function asPrettyXml($string)
    {
        $string = preg_replace("/>\\s*</", ">\n<", $string);
        $xmlArray = explode("\n", $string);
        $currIndent = 0;
        $indent = "    ";
        $string = array_shift($xmlArray) . "\n";
        foreach ($xmlArray as $element) {
            if (preg_match('/^<([\w])+[^>\/]*>$/U', $element)) {
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
                $currIndent += 1;
            } elseif (preg_match('/^<\/.+>$/', $element)) {
                $currIndent -= 1;
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
            } else {
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
            }
        }

        return $string;
    }
}
