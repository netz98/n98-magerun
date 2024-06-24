<?php

declare(strict_types=1);

namespace N98\Magento\Command\Script\Repository;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Run script command
 *
 * @package N98\Magento\Command\Script\Repository
 */
class RunCommand extends AbstractRepositoryCommand
{
    public const COMMAND_ARGUMENT_SCRIPT = 'script';

    public const COMMAND_OPTION_DEFINE = 'define';

    public const COMMAND_OPTION_STOP_ON_ERROR = 'stop-on-error';

    /**
     * @var string
     */
    protected static $defaultName = 'script:repo:run';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Run script from repository.';

    protected static bool $initMagentoFlag = false;

    protected static bool $detectMagentoFlag = false;

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_SCRIPT,
                InputArgument::OPTIONAL,
                'Name of script in repository'
            )
            ->addOption(
                self::COMMAND_OPTION_DEFINE,
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Defines a variable'
            )
            ->addOption(
                self::COMMAND_OPTION_STOP_ON_ERROR,
                null,
                InputOption::VALUE_NONE,
                'Stops execution of script on error'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Please note that the script repo command runs only scripts which are stored
in a defined script folder.

Script folders can be defined by config.

Example:

script:
  folders:
    - /my/script_folder


There are some pre-defined script folders:

- /usr/local/share/n98-magerun/scripts
- ~/.n98-magerun/scripts

If you like to run a standalone script you can also use the "script" command.

See: n98-magerun.phar script <filename.magerun>

HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getScripts();
        if ($input->getArgument(self::COMMAND_ARGUMENT_SCRIPT) == null && $input->isInteractive()) {
            $choices = [];
            foreach ($files as $file) {
                $files[] = $file;
                $choices[] = $file['fileinfo']->getFilename();
            }

            $validator = function ($typeInput) use ($files) {
                if (!isset($files[$typeInput])) {
                    throw new InvalidArgumentException('Invalid file');
                }

                return $files[$typeInput]['fileinfo']->getPathname();
            };

            $dialog = $this->getQuestionHelper();
            $question = new ChoiceQuestion(
                '<question>Please select a script file:</question> ',
                $choices
            );
            $question->setValidator($validator);

            $selectedFile = $dialog->ask($input, $output, $question);
        } else {
            $script = $input->getArgument(self::COMMAND_ARGUMENT_SCRIPT);
            if (is_string($script) && !str_ends_with($script, self::MAGERUN_EXTENSION)) {
                $script .= self::MAGERUN_EXTENSION;
            }

            if (!isset($files[$script])) {
                throw new InvalidArgumentException('Invalid script');
            }
            $selectedFile = $files[$script]['fileinfo']->getPathname();
        }

        $scriptArray = [
            'command'  => 'script',
            'filename' => $selectedFile
        ];

        /** @var array<int, string> $defined */
        $defined = $input->getOption(self::COMMAND_OPTION_DEFINE);
        foreach ($defined as $define) {
            $scriptArray['--define'][] = $define;
        }
        if ($input->getOption(self::COMMAND_OPTION_STOP_ON_ERROR)) {
            $scriptArray['--stop-on-error'] = true;
        }
        $input = new ArrayInput($scriptArray);
        $this->getApplication()->run($input, $output);

        return Command::SUCCESS;
    }
}
