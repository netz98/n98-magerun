<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use Exception;
use Mage;
use N98\Magento\Command\AbstractCommand;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 *  Update module command
 *
 *  @package N98\Magento\Command\Developer\Module
 */
class UpdateCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_VENDOR = 'vendorNamespace';

    public const COMMAND_ARGUMENT_MODULE = 'moduleName';

    public const COMMAND_OPTION_ADD_ALL = 'add-all';

    public const COMMAND_OPTION_ADD_BLOCKS = 'add-blocks';

    public const COMMAND_OPTION_ADD_DEFAULT = 'add-default';

    public const COMMAND_OPTION_ADD_EVENTS = 'add-events';

    public const COMMAND_OPTION_ADD_HELPERS = 'add-helpers';

    public const COMMAND_OPTION_ADD_LAYOUT_UPDATES = 'add-layout-updates';

    public const COMMAND_OPTION_ADD_MODELS = 'add-models';

    public const COMMAND_OPTION_ADD_RESOURCE_MODEL = 'add-resource-model';

    public const COMMAND_OPTION_ADD_ROUTERS = 'add-routers';

    public const COMMAND_OPTION_ADD_TRANSLATE = 'add-translate';

    public const COMMAND_OPTION_SET_VERSION = 'set-version';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:update';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Updates a module.';

    /**
     * @var string
     */
    protected string $baseFolder;

    /**
     * @var string
     */
    protected string $moduleDirectory;

    /**
     * @var string
     */
    protected string $vendorNamespace;

    /**
     * @var string
     */
    protected string $moduleName;

    /**
     * @var string
     */
    protected string $codePool;

    /**
     * @var array
     *
     * @phpstan-ignore missingType.iterableValue(@TODO(sr))
     */
    protected array $configNodes = [];

    /**
     * @var bool
     */
    protected bool $testMode = false;

    /**
     * @param bool $testMode
     */
    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }

    /**
     * @return bool
     */
    public function getTestMode(): bool
    {
        return $this->testMode;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_VENDOR,
                InputArgument::REQUIRED,
                'Namespace (your company prefix)'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_MODULE,
                InputArgument::REQUIRED,
                'Name of your module.'
            )
            ->addOption(
                self::COMMAND_OPTION_SET_VERSION,
                null,
                InputOption::VALUE_NONE,
                'Set module version in config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_BLOCKS,
                null,
                InputOption::VALUE_NONE,
                'Adds blocks class to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_HELPERS,
                null,
                InputOption::VALUE_NONE,
                'Adds helpers class to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_MODELS,
                null,
                InputOption::VALUE_NONE,
                'Adds models class to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_ALL,
                null,
                InputOption::VALUE_NONE,
                'Adds blocks, helpers and models classes to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_RESOURCE_MODEL,
                null,
                InputOption::VALUE_NONE,
                'Adds resource model class and entities to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_ROUTERS,
                null,
                InputOption::VALUE_NONE,
                'Adds routers for frontend or admin areas to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_EVENTS,
                null,
                InputOption::VALUE_NONE,
                'Adds events observer to global, frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_LAYOUT_UPDATES,
                null,
                InputOption::VALUE_NONE,
                'Adds layout updates to frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_TRANSLATE,
                null,
                InputOption::VALUE_NONE,
                'Adds translate configuration to frontend or adminhtml areas to config.xml'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_DEFAULT,
                null,
                InputOption::VALUE_NONE,
                'Adds default value (related to system.xml groups/fields)'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initMagento();
        $this->initArguments($input);

        if ($this->hasAddResourceModelOption($input)) {
            $this->askResourceModelOptions($input, $output);
        }

        if ($this->hasAddRoutersOption($input)) {
            $this->askRoutersOptions($input, $output);
        }

        if ($this->hasAddEventsOption($input)) {
            $this->askEventsOptions($input, $output);
        }

        if ($this->hasAddLayoutUpdatesOptions($input)) {
            $this->askLayoutUpdatesOptions($input, $output);
        }

        if ($this->hasAddTranslateOption($input)) {
            $this->askTranslateOptions($input, $output);
        }

        if ($this->hasAddDefaultOption($input)) {
            $this->askDefaultOptions($input, $output);
        }

        $this->setModuleDirectory($this->getModuleDir());
        $this->writeModuleConfig($input, $output);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     */
    protected function initArguments(InputInterface $input): void
    {
        /** @var string $vendorNamespace */
        $vendorNamespace = $input->getArgument(self::COMMAND_ARGUMENT_VENDOR);
        /** @var string $moduleName */
        $moduleName = $input->getArgument(self::COMMAND_ARGUMENT_MODULE);

        $this->vendorNamespace = ucfirst($vendorNamespace);
        $this->moduleName = ucfirst($moduleName);
        $this->determineModuleCodePool();
    }

    /**
     * Find module codepool from module directory
     *
     * @return string
     */
    protected function determineModuleCodePool(): string
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
    protected function setModuleDirectory(string $moduleDir): void
    {
        if (!file_exists($moduleDir)) {
            throw new RuntimeException(
                'Module does not exist. Use dev:module:create to create it before updating. Stop.'
            );
        }

        $this->moduleDirectory = $moduleDir;
    }

    /**
     * Writes module config file for given options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function writeModuleConfig(InputInterface $input, OutputInterface $output): void
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
    protected function setVersion(InputInterface $input, OutputInterface $output, SimpleXMLElement $configXml): void
    {
        if ($this->shouldSetVersion($input)) {
            $modulesNode = $configXml->modules->{$this->getModuleNamespace()};

            $dialog = $this->getQuestionHelper();
            $question = new Question('<question>Enter version number:</question> ');
            /** @var string $version */
            $version = $dialog->ask($input, $output, $question);
            $version = trim($version);
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
    protected function setGlobalNode(InputInterface $input, OutputInterface $output, SimpleXMLElement $configXml): void
    {
        if ($this->shouldAddAll($input)) {
            $this->addGlobalNode($configXml, 'blocks', '_Block');
            $this->addGlobalNode($configXml, 'helpers', '_Helper');
            $this->addGlobalNode($configXml, 'models', '_Model');
            $this->addResourceModelNodeIfConfirmed($input, $output, $configXml);
        } else {
            if ($this->shouldAddBlocks($input)) {
                $this->addGlobalNode($configXml, 'blocks', '_Block');
            }

            if ($this->shouldAddHelpers($input)) {
                $this->addGlobalNode($configXml, 'helpers', '_Helper');
            }

            if ($this->shouldAddModels($input)) {
                $this->addGlobalNode($configXml, 'models', '_Model');
                $this->addResourceModelNodeIfConfirmed($input, $output, $configXml);
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param SimpleXMLElement $configXml
     */
    protected function addResourceModelNodeIfConfirmed(
        InputInterface $input,
        OutputInterface $output,
        SimpleXMLElement $configXml
    ): void {
        $dialog = $this->getQuestionHelper();

        $question = new ConfirmationQuestion(
            '<question>Would you like to also add a Resource Model(y/n)?</question>',
            false
        );

        if ($dialog->ask($input, $output, $question)) {
            $question = new Question('<question>Resource Model:</question> ');
            /** @var string $resourceModel */
            $resourceModel = $dialog->ask($input, $output, $question);
            $resourceModel = trim($resourceModel);
            $configXml->global->models
                ->{$this->getLowercaseModuleNamespace()}->addChild('resourceModel', $resourceModel);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setResourceModelNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddResourceModelOption($input)) {
            $this->addResourceModel($configXml);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setRoutersNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddRoutersOption($input)) {
            $this->addRouter($configXml, $this->configNodes['router_area']);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setEventsNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddEventsOption($input)) {
            $this->addEvent($configXml, $this->configNodes['events_area'], $this->configNodes['event_name']);
        }
    }

    /**
     * @param InputInterface $input
     * @param SimpleXMLElement $configXml
     */
    protected function setLayoutUpdatesNode(InputInterface $input, SimpleXMLElement $configXml): void
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
    protected function setTranslateNode(InputInterface $input, SimpleXMLElement $configXml): void
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
    protected function setDefaultNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddDefaultOption($input)) {
            $this->addDefault($configXml);
        }
    }

    /**
     * Gets config XML
     *
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected function getConfigXml(): SimpleXMLElement
    {
        /** @var string $currentConfigXml */
        $currentConfigXml = $this->getCurrentConfigContent();
        return new SimpleXMLElement($currentConfigXml);
    }

    /**
     * Returns current content of /etc/config.xml
     *
     * @return string|false
     */
    protected function getCurrentConfigContent()
    {
        $configFile = $this->getModuleDir() . '/etc/config.xml';

        return file_get_contents($configFile);
    }

    /**
     * @return string
     */
    protected function getModuleDir(): string
    {
        return $this->moduleDirectory ?? Mage::getModuleDir('', $this->getModuleNamespace());
    }

    /**
     * Initiates resource nodes specific values
     */
    protected function initResourceModelConfigNodes(): void
    {
        $this->configNodes['resource_node_name'] = $this->getLowercaseModuleNamespace() . '_resource';
        $this->configNodes['resource_model_class'] = $this->getModuleNamespace() . '_Model_Resource';
        $this->configNodes['resource_deprecated_mysql4_node'] = false;
        $this->configNodes['resource_entities'] = [];
    }

    /**
     * Initiates routers config nodes specific values
     */
    protected function initRoutersConfigNodes(): void
    {
        $this->configNodes['router_area'] = false;
        $this->configNodes['use'] = false;
        $this->configNodes['frontname'] = false;
    }

    /**
     * Initiates events config nodes specific values
     */
    protected function initEventsConfigNodes(): void
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
    protected function initLayoutUpdatesConfigNodes(): void
    {
        $this->configNodes['layout_updates_area'] = false;
        $this->configNodes['layout_update_module'] = false;
        $this->configNodes['layout_update_file'] = false;
    }

    /**
     * Initiates layout updates nodes specific values
     */
    protected function initTranslateConfigNodes(): void
    {
        $this->configNodes['translate_area'] = false;
        $this->configNodes['translate_module'] = $this->getModuleNamespace();
        $this->configNodes['translate_files_default'] = false;
    }

    /**
     * Initiates resource nodes specific values
     */
    protected function initDefaultConfigNodes(): void
    {
        $this->configNodes['default_section_name'] = false;
        $this->configNodes['default_group_name'] = false;
        $this->configNodes['default_field_name'] = false;
        $this->configNodes['default_field_value'] = false;
    }

    /**
     * Asks for routers node options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askResourceModelOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initResourceModelConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new ConfirmationQuestion(
            '<question>Would you like to set mysql4 deprecated node(y/n)?</question>',
            false
        );
        if ($dialog->ask($input, $output, $question)) {
            $this->configNodes['resource_deprecated_mysql4_node'] = true;
        }

        $entityName = true;

        while ($entityName) {
            $question = new Question('<question>Entity Name (leave blank to exit):</question> ');
            /** @var string $entityName */
            $entityName = $dialog->ask($input, $output, $question);
            $entityName = trim($entityName);
            if (!$entityName) {
                break;
            }

            $question = new Question('<question>Entity Table:</question> ');
            /** @var string $entityTable */
            $entityTable = $dialog->ask($input, $output, $question);
            $entityTable = trim($entityTable);
            $this->configNodes['resource_entities'][$entityName] = $entityTable;
        }
    }

    /**
     * Asks for routers node options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askRoutersOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initRoutersConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin']
        );
        /** @var string $area */
        $area = $dialog->ask($input, $output, $question);
        $area = trim($area);

        $question = new Question('<question>Use:</question> ');
        /** @var string $use */
        $use = $dialog->ask($input, $output, $question);
        $use = trim($use);

        $question = new Question('<question>Frontname:</question> ');
        /** @var string $frontName */
        $frontName = $dialog->ask($input, $output, $question);
        $frontName = trim($frontName);

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askEventsOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initEventsConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (global|frontend|adminhtml):</question> ',
            ['global', 'frontend', 'admin']
        );
        /** @var string $area */
        $area = $dialog->ask($input, $output, $question);
        $area = trim($area);

        $question = new Question('<question>Event:</question> ');
        /** @var string $event */
        $event = $dialog->ask($input, $output, $question);
        $event = trim($event);

        $question = new Question('<question>Event Observer:</question> ');
        /** @var string $observer */
        $observer = $dialog->ask($input, $output, $question);
        $observer = trim($observer);

        $question = new Question('<question>Event Observer Class:</question> ');
        /** @var string $observerClass */
        $observerClass = $dialog->ask($input, $output, $question);
        $observerClass = trim($observerClass);

        $question = new Question('<question>Event Observer Method:</question> ');
        /** @var string $observerMethod */
        $observerMethod = $dialog->ask($input, $output, $question);
        $observerMethod = trim($observerMethod);

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askLayoutUpdatesOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initLayoutUpdatesConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin']
        );
        /** @var string $area */
        $area = $dialog->ask($input, $output, $question);
        $area = trim($area);

        $question = new Question('<question>Module:</question> ');
        /** @var string $module */
        $module = $dialog->ask($input, $output, $question);
        $module = trim($module);

        $question = new Question('<question>File:</question> ');
        /** @var string $file */
        $file = $dialog->ask($input, $output, $question);
        $file = trim($file);

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     */
    protected function askTranslateOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initTranslateConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin']
        );
        /** @var string $area */
        $area = $dialog->ask($input, $output, $question);
        $area = trim($area);

        $question = new Question('<question>File:</question> ');
        /** @var string $file */
        $file = $dialog->ask($input, $output, $question);
        $file = trim($file);

        if ($area != 'frontend' && $area != 'adminhtml') {
            throw new RuntimeException('Layout updates area must be either "frontend" or "adminhtml"');
        }

        $this->configNodes['translate_area'] = $area;
        $this->configNodes['translate_files_default'] = $file;
    }

    /**
     * Asks for default node options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function askDefaultOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initDefaultConfigNodes();

        $dialog = $this->getQuestionHelper();

        $question = new Question('<question>Section Name (lowercase):</question> ');
        /** @var string $sectionName */
        $sectionName = $dialog->ask($input, $output, $question);
        $sectionName = strtolower(trim($sectionName));

        $question = new Question('<question>Group Name (lowercase):</question> ');
        /** @var string $groupName */
        $groupName = $dialog->ask($input, $output, $question);
        $groupName = strtolower(trim($groupName));

        $question = new Question('<question>Field Name:</question> ');
        /** @var string $fieldName */
        $fieldName = $dialog->ask($input, $output, $question);
        $fieldName = strtolower(trim($fieldName));

        $question = new Question('<question>Field Value:</question> ');
        /** @var string $fieldValue */
        $fieldValue = $dialog->ask($input, $output, $question);
        $fieldValue = strtolower(trim($fieldValue));

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
    protected function addGlobalNode(SimpleXMLElement $configXml, string $type, string $classSuffix): void
    {
        $this->removeChildNodeIfNotNull($configXml->global, $type);
        $global = $configXml->global ?: $configXml->addChild('global');
        $globalNode = $global->addChild($type);
        $moduleNamespaceNode = $globalNode->addChild($this->getLowercaseModuleNamespace());
        $moduleNamespaceNode->addChild('class', $this->getModuleNamespace() . $classSuffix);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     */
    protected function addResourceModel(SimpleXMLElement $simpleXml): void
    {
        if (is_null($simpleXml->global->models)) {
            throw new RuntimeException(
                'Global models node is not set. Run --add-models before --add-resource-model command.'
            );
        }

        $resourceNamespace = $this->getLowercaseModuleNamespace() . '_resource';
        $resourceModelNode = $simpleXml->global->models->$resourceNamespace ?: $simpleXml->global->models->addChild($resourceNamespace);

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

        $entitiesNode = $resourceModelNode->entities ?: $resourceModelNode->addChild('entities');

        foreach ($this->configNodes['resource_entities'] as $entity => $table) {
            $this->removeChildNodeIfNotNull($entitiesNode, $entity);
            $entityNode = $entitiesNode->addChild($entity);
            $entityNode->addChild('table', $table);
        }
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param string $area
     */
    protected function addRouter(SimpleXMLElement $simpleXml, string $area): void
    {
        $this->removeChildNodeIfNotNull($simpleXml->{$area}, 'routers');
        $areaNode = $simpleXml->{$area} ?: $simpleXml->addChild($area);
        $routers = $areaNode->addChild('routers');
        $moduleNamespace = $routers->addChild($this->getLowercaseModuleNamespace());
        $moduleNamespace->addChild('use', $this->configNodes['use']);
        $args = $moduleNamespace->addChild('args');
        $args->addChild('module', $this->getLowercaseModuleNamespace());
        $args->addChild('frontName', $this->configNodes['frontname']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param string $area
     * @param string $event
     */
    protected function addEvent(SimpleXMLElement $simpleXml, string $area, string $event): void
    {
        $areaNode = $simpleXml->{$area} ?: $simpleXml->addChild($area);
        $eventsNode = $areaNode->events ?: $areaNode->addChild('events');
        $this->removeChildNodeIfNotNull($eventsNode, $event);
        $eventNode = $eventsNode->addChild($event);
        $observersNode = $eventNode->addChild('observers');
        $eventObserverNode = $observersNode->addChild($this->configNodes['event_observer']);
        $eventObserverNode->addChild('class', $this->configNodes['event_observer_class']);
        $eventObserverNode->addChild('method', $this->configNodes['event_observer_method']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param string $area
     * @param string $module
     */
    protected function addLayoutUpdate(SimpleXMLElement $simpleXml, string $area, string $module): void
    {
        $areaNode = $simpleXml->{$area} ?: $simpleXml->addChild($area);
        $layoutNode = $areaNode->layout ?: $areaNode->addChild('layout');
        $updatesNode = $layoutNode->updates ?: $layoutNode->addChild('updates');
        $this->removeChildNodeIfNotNull($updatesNode, $module);
        $moduleNode = $updatesNode->addChild($module);
        $moduleNode->addChild('file', $this->configNodes['layout_update_file']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     * @param string $area
     * @param string $module
     */
    protected function addTranslate(SimpleXMLElement $simpleXml, string $area, string $module): void
    {
        $areaNode = $simpleXml->{$area} ?: $simpleXml->addChild($area);
        $translateNode = $areaNode->translate ?: $areaNode->addChild('translate');
        $modulesNode = $translateNode->modules ?: $translateNode->addChild('modules');
        $this->removeChildNodeIfNotNull($modulesNode, $module);
        $moduleNode = $modulesNode->addChild($this->configNodes['translate_module']);
        $filesNode = $moduleNode->addChild('files');
        $filesNode->addChild('default', $this->configNodes['translate_files_default']);
    }

    /**
     * @param SimpleXMLElement $simpleXml
     */
    protected function addDefault(SimpleXMLElement $simpleXml): void
    {
        $defaultNode = $simpleXml->default ?: $simpleXml->addChild('default');
        $sectionNode = $defaultNode->{$this->configNodes['default_section_name']} ?: $defaultNode->addChild($this->configNodes['default_section_name']);
        $groupNode = $sectionNode->{$this->configNodes['default_group_name']} ?: $sectionNode->addChild($this->configNodes['default_group_name']);
        $this->removeChildNodeIfNotNull($groupNode, $this->configNodes['default_field_name']);
        $groupNode->addChild($this->configNodes['default_field_name'], $this->configNodes['default_field_value']);
    }

    /**
     * @return string
     */
    protected function getOutFile(): string
    {
        return $this->moduleDirectory . '/etc/config.xml';
    }

    /**
     * @param SimpleXMLElement $configXml
     */
    protected function putConfigXml(SimpleXMLElement $configXml): void
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
        return $input->getOption(self::COMMAND_OPTION_ADD_RESOURCE_MODEL);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddRoutersOption(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_ROUTERS);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddEventsOption(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_EVENTS);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddLayoutUpdatesOptions(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_LAYOUT_UPDATES);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddTranslateOption(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_TRANSLATE);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function hasAddDefaultOption(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_DEFAULT);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldSetVersion(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_SET_VERSION);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddBlocks(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_BLOCKS);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddHelpers(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_HELPERS);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddModels(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_MODELS);
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    protected function shouldAddAll(InputInterface $input)
    {
        return $input->getOption(self::COMMAND_OPTION_ADD_ALL);
    }

    /**
     * Gets module namespace e.g. Company_Modulename
     *
     * @return string
     */
    protected function getModuleNamespace(): string
    {
        return $this->vendorNamespace . '_' . $this->moduleName;
    }

    /**
     * @return string
     */
    protected function getLowercaseModuleNamespace(): string
    {
        return strtolower($this->vendorNamespace . '_' . $this->moduleName);
    }

    /**
     * @return string
     */
    protected function getLowercaseModuleName(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Removes a child node if not null.
     * Deals with duplications of nodes when already in config
     *
     * @param SimpleXMLElement $node
     * @param string $child
     */
    protected function removeChildNodeIfNotNull(SimpleXMLElement $node, string $child): void
    {
        if (!is_null($node->{$child})) {
            unset($node->{$child});
        }
    }

    /**
     * Formats given string as pretty xml
     *
     * @param string $string
     * @return string
     */
    protected function asPrettyXml(string $string): string
    {
        /** @var string $string */
        $string = preg_replace("/>\\s*</", ">\n<", $string);
        $xmlArray = explode("\n", $string);
        $currIndent = 0;
        $indent = "    ";
        $string = array_shift($xmlArray) . "\n";
        foreach ($xmlArray as $element) {
            if (preg_match('/^<([\w])+[^>\/]*>$/U', $element)) {
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
                ++$currIndent;
            } elseif (preg_match('/^<\/.+>$/', $element)) {
                --$currIndent;
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
            } else {
                $string .= str_repeat($indent, $currIndent) . $element . "\n";
            }
        }

        return $string;
    }
}
