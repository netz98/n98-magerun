<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use Exception;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
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
 * Update module command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class UpdateCommand extends AbstractMagentoCommand
{
    protected ?string $moduleDirectory;

    protected string $vendorNamespace;

    protected string $moduleName;

    protected string $codePool;

    protected array $configNodes = [];

    protected bool $testMode = false;

    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }

    public function getTestMode(): bool
    {
        return $this->testMode;
    }

    protected function configure(): void
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
                'Adds blocks, helpers and models classes to config.xml',
            )
            ->addOption(
                'add-resource-model',
                null,
                InputOption::VALUE_NONE,
                'Adds resource model class and entities to config.xml',
            )
            ->addOption(
                'add-routers',
                null,
                InputOption::VALUE_NONE,
                'Adds routers for frontend or admin areas to config.xml',
            )
            ->addOption(
                'add-events',
                null,
                InputOption::VALUE_NONE,
                'Adds events observer to global, frontend or adminhtml areas to config.xml',
            )
            ->addOption(
                'add-layout-updates',
                null,
                InputOption::VALUE_NONE,
                'Adds layout updates to frontend or adminhtml areas to config.xml',
            )
            ->addOption(
                'add-translate',
                null,
                InputOption::VALUE_NONE,
                'Adds translate configuration to frontend or adminhtml areas to config.xml',
            )
            ->addOption(
                'add-default',
                null,
                InputOption::VALUE_NONE,
                'Adds default value (related to system.xml groups/fields)',
            )
            ->setDescription('Update a Magento module.');
    }


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

    protected function initArguments(InputInterface $input): void
    {
        $this->vendorNamespace = ucfirst($input->getArgument('vendorNamespace'));
        $this->moduleName = ucfirst($input->getArgument('moduleName'));
        $this->determineModuleCodePool();
    }

    /**
     * Find module codepool from module directory
     */
    protected function determineModuleCodePool(): string
    {
        if ($this->testMode) {
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
     * @throws RuntimeException
     */
    protected function setModuleDirectory(string $moduleDir): void
    {
        if (!file_exists($moduleDir)) {
            throw new RuntimeException(
                'Module does not exist. Use dev:module:create to create it before updating. Stop.',
            );
        }

        $this->moduleDirectory = $moduleDir;
    }

    /**
     * Writes module config file for given options
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

    protected function setVersion(InputInterface $input, OutputInterface $output, SimpleXMLElement $configXml): void
    {
        if ($this->shouldSetVersion($input)) {
            $modulesNode = $configXml->modules->{$this->getModuleNamespace()};

            $dialog = $this->getQuestionHelper();
            $question = new Question('<question>Enter version number:</question> ');
            $version = trim($dialog->ask($input, $output, $question));
            $modulesNode->version = $version;
        }
    }

    /**
     * Sets global xml config node
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

    protected function addResourceModelNodeIfConfirmed(InputInterface $input, OutputInterface $output, SimpleXMLElement $configXml): void
    {
        $questionHelper = $this->getQuestionHelper();

        $question = new ConfirmationQuestion(
            '<question>Would you like to also add a Resource Model(y/n)?</question>',
            false,
        );

        if ($questionHelper->ask($input, $output, $question)) {
            $question = new Question('<question>Resource Model:</question> ');
            $resourceModel = trim($questionHelper->ask($input, $output, $question));
            $configXml->global->models
                ->{$this->getLowercaseModuleNamespace()}->addChild('resourceModel', $resourceModel);
        }
    }

    protected function setResourceModelNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddResourceModelOption($input)) {
            $this->addResourceModel($configXml);
        }
    }

    protected function setRoutersNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddRoutersOption($input)) {
            $this->addRouter($configXml, $this->configNodes['router_area']);
        }
    }

    protected function setEventsNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddEventsOption($input)) {
            $this->addEvent($configXml, $this->configNodes['events_area'], $this->configNodes['event_name']);
        }
    }

    protected function setLayoutUpdatesNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddLayoutUpdatesOptions($input)) {
            $this->addLayoutUpdate(
                $configXml,
                $this->configNodes['layout_updates_area'],
                $this->configNodes['layout_update_module'],
            );
        }
    }

    protected function setTranslateNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddTranslateOption($input)) {
            $this->addTranslate(
                $configXml,
                $this->configNodes['translate_area'],
                $this->configNodes['translate_module'],
            );
        }
    }

    protected function setDefaultNode(InputInterface $input, SimpleXMLElement $configXml): void
    {
        if ($this->hasAddDefaultOption($input)) {
            $this->addDefault($configXml);
        }
    }

    /**
     * Gets config XML
     * @throws Exception
     */
    protected function getConfigXml(): SimpleXMLElement
    {
        $currentConfigXml = $this->getCurrentConfigContent();
        return new SimpleXMLElement($currentConfigXml);
    }

    /**
     * Returns current content of /etc/config.xml
     */
    protected function getCurrentConfigContent(): string
    {
        $configFile = $this->getModuleDir() . '/etc/config.xml';
        return (string) file_get_contents($configFile);
    }

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
     * @throws RuntimeException
     */
    protected function askResourceModelOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initResourceModelConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new ConfirmationQuestion(
            '<question>Would you like to set mysql4 deprecated node(y/n)?</question>',
            false,
        );
        if ($questionHelper->ask($input, $output, $question)) {
            $this->configNodes['resource_deprecated_mysql4_node'] = true;
        }

        $entityName = true;

        while ($entityName) {
            $question = new Question('<question>Entity Name (leave blank to exit):</question> ');
            $entityName = trim($questionHelper->ask($input, $output, $question));
            if ($entityName === '' || $entityName === '0') {
                break;
            }

            $question = new Question('<question>Entity Table:</question> ');
            $entityTable = trim($questionHelper->ask($input, $output, $question));
            $this->configNodes['resource_entities'][$entityName] = $entityTable;
        }
    }

    /**
     * Asks for routers node options
     *
     * @throws RuntimeException
     */
    protected function askRoutersOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initRoutersConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin'],
        );
        $area = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Use:</question> ');
        $use = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Frontname:</question> ');
        $frontName = trim($questionHelper->ask($input, $output, $question));

        if ($area !== 'frontend' && $area !== 'admin') {
            throw new RuntimeException('Router area must be either "frontend" or "admin"');
        }

        $this->configNodes['router_area'] = $area;
        $this->configNodes['use'] = $use;
        $this->configNodes['frontname'] = $frontName;
    }

    /**
     * Asks for events node options
     *
     * @throws RuntimeException
     */
    protected function askEventsOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initEventsConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (global|frontend|adminhtml):</question> ',
            ['global', 'frontend', 'admin'],
        );
        $area = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Event:</question> ');
        $event = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Event Observer:</question> ');
        $observer = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Event Observer Class:</question> ');
        $observerClass = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Event Observer Method:</question> ');
        $observerMethod = trim($questionHelper->ask($input, $output, $question));

        if (!in_array($area, ['global', 'frontend', 'adminhtml'], true)) {
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
     * @throws RuntimeException
     */
    protected function askLayoutUpdatesOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initLayoutUpdatesConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin'],
        );
        $area = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>Module:</question> ');
        $module = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>File:</question> ');
        $file = trim($questionHelper->ask($input, $output, $question));

        if ($area !== 'frontend' && $area !== 'adminhtml') {
            throw new RuntimeException('Layout updates area must be either "frontend" or "adminhtml"');
        }

        $this->configNodes['layout_updates_area'] = $area;
        $this->configNodes['layout_update_module'] = $module;
        $this->configNodes['layout_update_file'] = $file;
    }

    /**
     * Asks for translate node options
     *
     * @throws RuntimeException
     */
    protected function askTranslateOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initTranslateConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new ChoiceQuestion(
            '<question>Area (frontend|admin):</question> ',
            ['frontend', 'admin'],
        );
        $area = trim($questionHelper->ask($input, $output, $question));

        $question = new Question('<question>File:</question> ');
        $file = trim($questionHelper->ask($input, $output, $question));

        if ($area !== 'frontend' && $area !== 'adminhtml') {
            throw new RuntimeException('Layout updates area must be either "frontend" or "adminhtml"');
        }

        $this->configNodes['translate_area'] = $area;
        $this->configNodes['translate_files_default'] = $file;
    }

    /**
     * Asks for default node options
     *
     * @throws RuntimeException
     */
    protected function askDefaultOptions(InputInterface $input, OutputInterface $output): void
    {
        $this->initDefaultConfigNodes();

        $questionHelper = $this->getQuestionHelper();

        $question = new Question('<question>Section Name (lowercase):</question> ');
        $sectionName = strtolower(trim($questionHelper->ask($input, $output, $question)));

        $question = new Question('<question>Group Name (lowercase):</question> ');
        $groupName = strtolower(trim($questionHelper->ask($input, $output, $question)));

        $question = new Question('<question>Field Name:</question> ');
        $fieldName = strtolower(trim($questionHelper->ask($input, $output, $question)));

        $question = new Question('<question>Field Value:</question> ');
        $fieldValue = strtolower(trim($questionHelper->ask($input, $output, $question)));

        $this->configNodes['default_section_name'] = $sectionName;
        $this->configNodes['default_group_name'] = $groupName;
        $this->configNodes['default_field_name'] = $fieldName;
        $this->configNodes['default_field_value'] = $fieldValue;
    }

    /**
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

    protected function addResourceModel(SimpleXMLElement $simpleXml): void
    {
        if (is_null($simpleXml->global->models)) {
            throw new RuntimeException(
                'Global models node is not set. Run --add-models before --add-resource-model command.',
            );
        }

        $resourceNamespace = $this->getLowercaseModuleNamespace() . '_resource';
        $resourceModelNode = $simpleXml->global->models->$resourceNamespace ?: $simpleXml->global->models->addChild($resourceNamespace);

        $simpleXml->global->models->$resourceNamespace->class
            ? null : $resourceModelNode->addChild('class', $this->configNodes['resource_model_class']);

        if ($this->configNodes['resource_deprecated_mysql4_node'] === true) {
            $simpleXml->global->models->$resourceNamespace->deprecatedNode ? null : $resourceModelNode->addChild(
                'deprecatedNode',
                $resourceNamespace . '_eav_mysql4',
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

    protected function addLayoutUpdate(SimpleXMLElement $simpleXml, string $area, string $module): void
    {
        $areaNode = $simpleXml->{$area} ?: $simpleXml->addChild($area);
        $layoutNode = $areaNode->layout ?: $areaNode->addChild('layout');
        $updatesNode = $layoutNode->updates ?: $layoutNode->addChild('updates');
        $this->removeChildNodeIfNotNull($updatesNode, $module);
        $moduleNode = $updatesNode->addChild($module);
        $moduleNode->addChild('file', $this->configNodes['layout_update_file']);
    }

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

    protected function addDefault(SimpleXMLElement $simpleXml): void
    {
        $defaultNode = $simpleXml->default ?: $simpleXml->addChild('default');
        $sectionNode = $defaultNode->{$this->configNodes['default_section_name']} ?: $defaultNode->addChild($this->configNodes['default_section_name']);
        $groupNode = $sectionNode->{$this->configNodes['default_group_name']} ?: $sectionNode->addChild($this->configNodes['default_group_name']);
        $this->removeChildNodeIfNotNull($groupNode, $this->configNodes['default_field_name']);
        $groupNode->addChild($this->configNodes['default_field_name'], $this->configNodes['default_field_value']);
    }

    protected function getOutFile(): string
    {
        return $this->moduleDirectory . '/etc/config.xml';
    }

    protected function putConfigXml(SimpleXMLElement $configXml): void
    {
        $outFile = $this->getOutFile();

        $xml = $configXml->asXML();
        if (false === $xml) {
            throw new RuntimeException('Failed to get XML from config SimpleXMLElement');
        }

        file_put_contents($outFile, $this->asPrettyXml($xml));
    }

    /**
     * @return mixed
     */
    protected function hasAddResourceModelOption(InputInterface $input)
    {
        return $input->getOption('add-resource-model');
    }

    /**
     * @return mixed
     */
    protected function hasAddRoutersOption(InputInterface $input)
    {
        return $input->getOption('add-routers');
    }

    /**
     * @return mixed
     */
    protected function hasAddEventsOption(InputInterface $input)
    {
        return $input->getOption('add-events');
    }

    /**
     * @return mixed
     */
    protected function hasAddLayoutUpdatesOptions(InputInterface $input)
    {
        return $input->getOption('add-layout-updates');
    }

    /**
     * @return mixed
     */
    protected function hasAddTranslateOption(InputInterface $input)
    {
        return $input->getOption('add-translate');
    }

    /**
     * @return mixed
     */
    protected function hasAddDefaultOption(InputInterface $input)
    {
        return $input->getOption('add-default');
    }

    /**
     * @return mixed
     */
    protected function shouldSetVersion(InputInterface $input)
    {
        return $input->getOption('set-version');
    }

    /**
     * @return mixed
     */
    protected function shouldAddBlocks(InputInterface $input)
    {
        return $input->getOption('add-blocks');
    }

    /**
     * @return mixed
     */
    protected function shouldAddHelpers(InputInterface $input)
    {
        return $input->getOption('add-helpers');
    }

    /**
     * @return mixed
     */
    protected function shouldAddModels(InputInterface $input)
    {
        return $input->getOption('add-models');
    }

    /**
     * @return mixed
     */
    protected function shouldAddAll(InputInterface $input)
    {
        return $input->getOption('add-all');
    }

    /**
     * Gets module namespace e.g. Company_ModuleName
     */
    protected function getModuleNamespace(): string
    {
        return $this->vendorNamespace . '_' . $this->moduleName;
    }

    protected function getLowercaseModuleNamespace(): string
    {
        return strtolower($this->vendorNamespace . '_' . $this->moduleName);
    }

    protected function getLowercaseModuleName(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Removes a child node if not null.
     * Deals with duplications of nodes when already in config
     */
    protected function removeChildNodeIfNotNull(SimpleXMLElement $node, string $child): void
    {
        if (!is_null($node->{$child})) {
            unset($node->{$child});
        }
    }

    /**
     * Formats given string as pretty xml
     */
    protected function asPrettyXml(string $string): string
    {
        $string = preg_replace('/>\\s*</', ">\n<", $string);
        $xmlArray = explode("\n", $string);
        $currIndent = 0;
        $indent = '    ';
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
