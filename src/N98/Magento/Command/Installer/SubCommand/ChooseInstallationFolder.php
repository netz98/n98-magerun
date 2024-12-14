<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer\SubCommand;

use InvalidArgumentException;
use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Question\Question;

use function chdir;
use function getcwd;

/**
 * Class ChooseInstallationFolder
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class ChooseInstallationFolder extends AbstractSubCommand
{
    public function execute(): void
    {
        $input = $this->input;
        $validateInstallationFolder = function ($folderName) {
            $folderName = rtrim(trim($folderName, ' '), '/');
            if ($folderName[0] === '.') {
                $cwd = getcwd();
                if ($cwd === false && isset($_SERVER['PWD'])) {
                    $cwd = $_SERVER['PWD'];
                }

                $folderName = $cwd . substr($folderName, 1);
            }

            if ($folderName === '' || $folderName === '0') {
                throw new InvalidArgumentException('Installation folder cannot be empty');
            }

            if (!is_dir($folderName)) {
                if (!mkdir($folderName, 0777, true) && !is_dir($folderName)) {
                    throw new InvalidArgumentException('Cannot create folder.');
                }

                return $folderName;
            }

            return $folderName;
        };

        $installationFolder = $input->getOption('installationFolder');
        if ($installationFolder === null) {
            $defaultFolder = './magento';
            $question = new Question(
                sprintf(
                    '<question>Enter installation folder:</question> [<comment>%s</comment>]',
                    $defaultFolder,
                ),
                $defaultFolder,
            );
            $question->setValidator($validateInstallationFolder);

            $installationFolder = $this->getCommand()->getQuestionHelper()->ask(
                $this->input,
                $this->output,
                $question,
            );
        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);
        }

        $this->config->setString('initialFolder', (string) getcwd());
        $this->config->setString('installationFolder', (string) realpath($installationFolder));
        chdir($this->config->getString('installationFolder'));
    }
}
