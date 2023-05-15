<?php

namespace N98\Magento\Command\Script\Repository;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RunCommand extends AbstractRepositoryCommand
{
    protected function configure()
    {
        $help = <<<HELP
Please note that the script repo command runs only scripts which are stored
in a defined script folder.

Script folders can defined by config.

Example:

script:
  folders:
    - /my/script_folder


There are some pre defined script folders:

- /usr/local/share/n98-magerun/scripts
- ~/.n98-magerun/scripts

If you like to run a standalone script you can also use the "script" command.

See: n98-magerun.phar script <filename.magerun>

HELP;

        $this
            ->setName('script:repo:run')
            ->addArgument('script', InputArgument::OPTIONAL, 'Name of script in repository')
            ->addOption('define', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Defines a variable')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stops execution of script on error')
            ->setDescription('Run script from repository')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->getScripts();
        if ($input->getArgument('script') == null && $input->isInteractive()) {
            $validator = function ($typeInput) use ($files) {
                if (!isset($files[$typeInput])) {
                    throw new InvalidArgumentException('Invalid file');
                }

                return $files[$typeInput]['fileinfo']->getPathname();
            };

            $choices = [];
            foreach ($files as $file) {
                $files[$i] = $file;
                $choices[] = $file['fileinfo']->getFilename();
            }

            /* @var QuestionHelper $dialog */
            $dialog = $this->getHelper('question');
            $question = new ChoiceQuestion(
                '<question>Please select a script file: </question>',
                $choices
            );
            $selectedFile = $dialog->ask($input, $output, $question);
        } else {
            $script = $input->getArgument('script');
            if (substr($script, -strlen(self::MAGERUN_EXTENSION)) !== self::MAGERUN_EXTENSION) {
                $script .= self::MAGERUN_EXTENSION;
            }

            if (!isset($files[$script])) {
                throw new InvalidArgumentException('Invalid script');
            }
            $selectedFile = $files[$script]['fileinfo']->getPathname();
        }

        $scriptArray = ['command'  => 'script', 'filename' => $selectedFile];
        foreach ($input->getOption('define') as $define) {
            $scriptArray['--define'][] = $define;
        }
        if ($input->getOption('stop-on-error')) {
            $scriptArray['--stop-on-error'] = true;
        }
        $input = new ArrayInput($scriptArray);
        $this->getApplication()->run($input, $output);
        return 0;
    }
}
