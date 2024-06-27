<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Create module skeleton command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class CreateCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_VENDOR = 'vendorNamespace';

    public const COMMAND_ARGUMENT_MODULE = 'moduleName';

    public const COMMAND_ARGUMENT_CODEPOOL = 'codePool';

    public const COMMAND_OPTION_ADD_ALL = 'add-all';

    public const COMMAND_OPTION_AUTHOR_EMAIL = 'author-email';

    public const COMMAND_OPTION_AUTHOR_NAME = 'author-name';

    public const COMMAND_OPTION_ADD_BLOCKS = 'add-blocks';

    public const COMMAND_OPTION_ADD_COMPOSER = 'add-composer';

    public const COMMAND_OPTION_ADD_CONTROLLER = 'add-controllers';

    public const COMMAND_OPTION_ADD_HELPERS = 'add-helpers';

    public const COMMAND_OPTION_ADD_MODELS = 'add-models';

    public const COMMAND_OPTION_ADD_README = 'add-readme';

    public const COMMAND_OPTION_ADD_SETUP = 'add-setup';

    public const COMMAND_OPTION_DESCRIPTION = 'description';

    public const COMMAND_OPTION_MODMAN = 'modman';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:create';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Creates and registers a new module.';

    protected static bool $initMagentoFlag = false;

    protected static bool $detectMagentoFlag = false;

    /**
     * @var array<string, string|bool|null>
     */
    protected array $twigVars = [];

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
     * @var bool
     */
    protected bool $modmanMode = false;

    /**
     * @var Filesystem
     *
     * phpcs:disable Ecg.PHP.PrivateClassMember.PrivateClassMemberError
     */
    private Filesystem $filesystem;

    public function __construct()
    {
        parent:: __construct();
        $this->filesystem = new Filesystem();
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
            ->addArgument(
                self::COMMAND_ARGUMENT_CODEPOOL,
                InputArgument::OPTIONAL,
                'Codepool (local, community)',
                'local',
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_CONTROLLER,
                null,
                InputOption::VALUE_NONE,
                'Adds controllers'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_BLOCKS,
                null,
                InputOption::VALUE_NONE,
                'Adds blocks'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_HELPERS,
                null,
                InputOption::VALUE_NONE,
                'Adds helpers'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_MODELS,
                null,
                InputOption::VALUE_NONE,
                'Adds models'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_SETUP,
                null,
                InputOption::VALUE_NONE,
                'Adds SQL setup'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_ALL,
                null,
                InputOption::VALUE_NONE,
                'Adds blocks, helpers and models'
            )
            ->addOption(
                self::COMMAND_OPTION_MODMAN,
                null,
                InputOption::VALUE_NONE,
                'Create all files in folder with a modman file.'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_README,
                null,
                InputOption::VALUE_NONE,
                'Adds a readme.md file to generated module'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_COMPOSER,
                null,
                InputOption::VALUE_NONE,
                'Adds a composer.json file to generated module'
            )
            ->addOption(
                self::COMMAND_OPTION_AUTHOR_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Author for readme.md or composer.json'
            )
            ->addOption(
                self::COMMAND_OPTION_AUTHOR_EMAIL,
                null,
                InputOption::VALUE_OPTIONAL,
                'Author for readme.md or composer.json'
            )
            ->addOption(
                self::COMMAND_OPTION_DESCRIPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Description for readme.md or composer.json'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var bool $modman */
        $modman = $input->getOption(self::COMMAND_OPTION_MODMAN);
        $this->modmanMode = $modman;
        if ($input->getOption(self::COMMAND_OPTION_ADD_ALL)) {
            $input->setOption(self::COMMAND_OPTION_ADD_CONTROLLER, true);
            $input->setOption(self::COMMAND_OPTION_ADD_BLOCKS, true);
            $input->setOption(self::COMMAND_OPTION_ADD_HELPERS, true);
            $input->setOption(self::COMMAND_OPTION_ADD_MODELS, true);
            $input->setOption(self::COMMAND_OPTION_ADD_SETUP, true);
            $input->setOption(self::COMMAND_OPTION_ADD_README, true);
            $input->setOption(self::COMMAND_OPTION_ADD_COMPOSER, true);
        }
        if (!$this->modmanMode) {
            $this->detectMagento($output);
        }

        $parameterHelper = $this->getParameterHelper();

        // Vendor
        $vendorNamespace = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_VENDOR, $input, $output);
        $input->setArgument(self::COMMAND_ARGUMENT_VENDOR, ucfirst($vendorNamespace));

        // Module
        $moduleName = $this->getOrAskForArgument(self::COMMAND_ARGUMENT_MODULE, $input, $output);
        $input->setArgument(self::COMMAND_ARGUMENT_MODULE, ucfirst($moduleName));

        // Codepool
        $codePool = $parameterHelper->askCoodpool($input, $output, self::COMMAND_ARGUMENT_CODEPOOL);
        $input->setArgument(self::COMMAND_ARGUMENT_CODEPOOL, $codePool);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->baseFolder = __DIR__ . '/../../../../../../res/module/create';

        /** @var string $vendorNamespace */
        $vendorNamespace = $input->getArgument(self::COMMAND_ARGUMENT_VENDOR);
        /** @var string $moduleName */
        $moduleName = $input->getArgument(self::COMMAND_ARGUMENT_CODEPOOL);
        /** @var string $codePool */
        $codePool = $input->getArgument(self::COMMAND_ARGUMENT_CODEPOOL);

        $this->vendorNamespace = $vendorNamespace;
        $this->moduleName = $moduleName;
        $this->codePool = $codePool;

        $this->initView($input);
        $this->createModuleDirectories($input, $output);
        $this->writeEtcModules($output);
        $this->writeModuleConfig($output);

        if ($input->getOption(self::COMMAND_OPTION_ADD_README)) {
            $this->writeReadme($output);
        }

        if ($this->modmanMode) {
            $this->writeModmanFile($output);
        }

        if ($input->getOption(self::COMMAND_OPTION_ADD_COMPOSER)) {
            $this->writeComposerConfig($output);
        }

        $this->addAdditionalFiles($output);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    protected function initView(InputInterface $input): void
    {
        /** @var bool|null $createControllers */
        $createControllers  = $input->getOption(self::COMMAND_OPTION_ADD_CONTROLLER);
        /** @var bool|null $createBlocks */
        $createBlocks       = $input->getOption(self::COMMAND_OPTION_ADD_BLOCKS);
        /** @var bool|null $createHelpers */
        $createHelpers      = $input->getOption(self::COMMAND_OPTION_ADD_HELPERS);
        /** @var bool|null $createModels */
        $createModels       = $input->getOption(self::COMMAND_OPTION_ADD_MODELS);
        /** @var bool|null $createSetup */
        $createSetup        = $input->getOption(self::COMMAND_OPTION_ADD_SETUP);
        /** @var string|null $authorName */
        $authorName         = $input->getOption(self::COMMAND_OPTION_AUTHOR_NAME);
        /** @var string|null $authorEmail */
        $authorEmail        = $input->getOption(self::COMMAND_OPTION_AUTHOR_EMAIL);
        /** @var string|null $description */
        $description        = $input->getOption(self::COMMAND_OPTION_DESCRIPTION);

        $this->twigVars = [
            'vendorNamespace'   => $this->vendorNamespace,
            'moduleName'        => $this->moduleName,
            'codePool'          => $this->codePool,
            'createControllers' => $createControllers,
            'createBlocks'      => $createBlocks,
            'createHelpers'     => $createHelpers,
            'createModels'      => $createModels,
            'createSetup'       => $createSetup,
            'authorName'        => $authorName,
            'authorEmail'       => $authorEmail,
            'description'       => $description
        ];
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     */
    protected function createModuleDirectories(InputInterface $input, OutputInterface $output): void
    {
        $this->createDirectories($output);
        $this->createBaseModuleDirectory($output);

        // Add etc folder
        $this->createModuleDirectory($output, 'etc');

        // Add controllers folder
        if ($input->getOption(self::COMMAND_OPTION_ADD_CONTROLLER)) {
            $this->createModuleDirectory($output, 'controllers');
        }

        // Add blocks folder
        if ($input->getOption(self::COMMAND_OPTION_ADD_BLOCKS)) {
            $this->createModuleDirectory($output, 'Block');
        }

        // Add helpers folder
        if ($input->getOption(self::COMMAND_OPTION_ADD_HELPERS)) {
            $this->createModuleDirectory($output, 'Helper');
        }

        // Add models folder
        if ($input->getOption(self::COMMAND_OPTION_ADD_MODELS)) {
            $this->createModuleDirectory($output, 'Model');
        }

        // Create SQL and Data folder
        if ($input->getOption(self::COMMAND_OPTION_ADD_SETUP)) {
            $this->createModuleSetupDirectory($output);
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $directory
     * @return void
     */
    private function createModuleDirectory(OutputInterface $output, string $directory): void
    {
        $path = $this->moduleDirectory . '/' . $directory;
        $this->createDirectory($output, $path);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function writeEtcModules(OutputInterface $output): void
    {
        $outFile = sprintf(
            '%s/app/etc/modules/%s_%s.xml',
            $this->_magentoRootFolder,
            $this->vendorNamespace,
            $this->moduleName
        );
        $template = 'dev/module/create/app/etc/modules/definition.twig';

        $this->dumpFile($output, $outFile, $template);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function writeModuleConfig(OutputInterface $output): void
    {
        $outFile = $this->moduleDirectory . '/etc/config.xml';
        $template = 'dev/module/create/app/etc/modules/config.twig';

        $this->dumpFile($output, $outFile, $template);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function writeModmanFile(OutputInterface $output): void
    {
        $outFile = $this->_magentoRootFolder . '/../modman';
        $template = 'dev/module/create/modman.twig';

        $this->dumpFile($output, $outFile, $template);
    }

    /**
     * Write standard readme
     *
     * TODO: Make author name / company URL and more configurable
     *
     * @see https://raw.github.com/sprankhub/Magento-Extension-Sample-Readme/master/readme.markdown
     *
     * @param OutputInterface $output
     * @return void
     */
    protected function writeReadme(OutputInterface $output): void
    {
        if ($this->modmanMode) {
            $outFile = $this->_magentoRootFolder . '/../readme.md';
        } else {
            $outFile = $this->moduleDirectory . '/etc/readme.md';
        }
        $template = 'dev/module/create/app/etc/modules/readme.twig';

        $this->dumpFile($output, $outFile, $template);
    }

    /**
     * Write composer.json
     *
     * @param OutputInterface $output
     */
    protected function writeComposerConfig(OutputInterface $output): void
    {
        if ($this->modmanMode) {
            $outFile = $this->_magentoRootFolder . '/../composer.json';
        } else {
            $outFile = $this->moduleDirectory . '/etc/composer.json';
        }
        $template = 'dev/module/create/composer.twig';

        $this->dumpFile($output, $outFile, $template);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    protected function addAdditionalFiles(OutputInterface $output): void
    {
        $config = $this->getCommandConfig();
        if (isset($config['additionalFiles']) && is_array($config['additionalFiles'])) {
            /**
             * @var string $template
             * @var string $outFileTemplate
             */
            foreach ($config['additionalFiles'] as $template => $outFileTemplate) {
                $outFile = $this->getOutfile($outFileTemplate);
                $outFileDir = Path::getRoot($outFile);
                if (!$this->filesystem->exists($outFileDir)) {
                    $this->filesystem->mkdir($outFileDir);
                }
                $this->dumpFile($output, $outFile, $template);
            }
        }
    }

    private function createDirectories(OutputInterface $output): void
    {
        if ($this->modmanMode) {
            $modManDir = $this->vendorNamespace . '_' . $this->moduleName . '/src';
            if ($this->filesystem->exists($modManDir)) {
                throw new RuntimeException('Module already exists. Stop.');
            }

            $this->createDirectory($output, $modManDir);

            $this->_magentoRootFolder = './' . $modManDir;
            $this->createDirectory($output, $this->_magentoRootFolder . '/app/etc/modules');
        }
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function createBaseModuleDirectory(OutputInterface $output): void
    {
        $moduleDir = sprintf(
            '%s/app/code/%s/%s/%s',
            $this->_magentoRootFolder,
            $this->codePool,
            $this->vendorNamespace,
            $this->moduleName
        );

        if ($this->filesystem->exists($moduleDir)) {
            throw new RuntimeException('Module already exists. Stop.');
        }
        $this->moduleDirectory = $moduleDir;
        $this->createDirectory($output, $this->moduleDirectory);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function createModuleSetupDirectory(OutputInterface $output): void
    {
        $sqlSetupFolder = sprintf(
            '%s/sql/%s_%s_setup',
            $this->moduleDirectory,
            strtolower($this->vendorNamespace),
            strtolower($this->moduleName)
        );
        $this->createDirectory($output, $sqlSetupFolder);

        $dataSetupFolder = sprintf(
            '%s/data/%s_%s_setup',
            $this->moduleDirectory,
            strtolower($this->vendorNamespace),
            strtolower($this->moduleName)
        );
        $this->createDirectory($output, $dataSetupFolder);
    }

    /**
     * @param OutputInterface $output
     * @param string $directory
     * @return void
     */
    private function createDirectory(OutputInterface $output, string $directory): void
    {
        try {
            $this->filesystem->mkdir($directory);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }
        $output->writeln(sprintf('<info>Created directory: <comment>%s<comment></info>', $directory));
    }

    /**
     * @param OutputInterface $output
     * @param string $outFile
     * @param string $template
     * @return void
     */
    private function dumpFile(OutputInterface $output, string $outFile, string $template): void
    {
        $helper = $this->getTwigHelper();
        $buffer = $helper->render($template, $this->twigVars);

        try {
            $this->filesystem->dumpFile($outFile, $buffer);
        } catch (IOExceptionInterface $exception) {
            throw new IOException($exception->getMessage());
        }
        $output->writeln(sprintf('<info>Created file: <comment>%s<comment></info>', $outFile));
    }

    /**
     * @param string $filename
     * @return string
     */
    private function getOutfile(string $filename): string
    {
        $paths = [
            'rootDir'   => $this->_magentoRootFolder,
            'moduleDir' => $this->moduleDirectory
        ];

        return $this->getTwigHelper()->renderString($filename, array_merge($this->twigVars, $paths));
    }
}
