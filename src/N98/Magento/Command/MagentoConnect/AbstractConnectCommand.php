<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractConnectCommand
 *
 * @package N98\Magento\Command\MagentoConnect
 */
abstract class AbstractConnectCommand extends AbstractMagentoCommand
{
    /**
     * @var string
     */
    protected $mageScript = null;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws RuntimeException
     */
    private function findMageScript(InputInterface $input, OutputInterface $output)
    {
        if ($this->mageScript === null) {
            $this->detectMagento($output);
            @chdir($this->_magentoRootFolder);
            $this->mageScript = './mage';
            if (!is_file($this->mageScript)) {
                throw new RuntimeException('Could not find "mage" shell script in current installation');
            }
            if (!is_executable($this->mageScript)) {
                if (!@chmod($this->mageScript, 0755)) {
                    throw new RuntimeException(
                        'Cannot make "mage" shell script executable. Please chmod the file manually.'
                    );
                }
            }
            if (!strstr(shell_exec($this->mageScript . ' list-channels'), 'community')) {
                // no channels available -> try to setup
                shell_exec($this->mageScript . ' mage-setup');
            }
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return function_exists('shell_exec') && !OperatingSystem::isWindows();
    }

    /**
     * @param string $line
     *
     * @return string[]
     */
    protected function matchConnectLine($line)
    {
        $matches = array();
        // expected format "Package: Version Stability[,Version Stability][,Version Stability]"
        $pattern = '/([a-zA-Z0-9-_]+):\s([0-9.]+)\s([a-z]+),?([0-9.]+)?\s?([a-z]+)?,?([0-9.]+)?\s?([a-z]+)?/';
        preg_match($pattern, $line, $matches);
        return $matches;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $mageScriptParams
     *
     * @return string
     */
    protected function callMageScript(InputInterface $input, OutputInterface $output, $mageScriptParams)
    {
        $this->findMageScript($input, $output);
        return shell_exec($this->mageScript . ' ' . $mageScriptParams);
    }

    /**
     * @param string $packageName
     * @param string $searchPackageName
     *
     * @return bool
     */
    protected function isAlternative($packageName, $searchPackageName)
    {
        $lev = levenshtein($packageName, $searchPackageName);

        return $lev <= strlen($searchPackageName) / 3 || false !== strpos($searchPackageName, $packageName);
    }

    /**
     * @param array           $alternatives
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function askForAlternativePackage($alternatives, OutputInterface $output)
    {
        return $this->askForArrayEntry($alternatives, $output, 'Use alternative package? :');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $found = false;
        $alternatives = array();
        $extensions = $this->callMageScript($input, $output, 'list-available');
        $searchPackage = $input->getArgument('package');
        foreach (preg_split('/' . PHP_EOL . '/', $extensions) as $line) {
            $matches = $this->matchConnectLine($line);
            if (!empty($matches)) {
                if ($matches[1] == $searchPackage) {
                    $found = true;
                    break 1;
                } else {
                    if ($this->isAlternative($matches[1], $searchPackage)) {
                        $alternatives[] = $matches[1];
                    }
                }
            }
        }

        if ($found) {
            $this->doAction($input, $output, $searchPackage);
        } else {
            $output->writeln('<comment>Could not find package.</comment>');
            if (count($alternatives) > 0) {
                $this->doAction($input, $output, $this->askForAlternativePackage($alternatives, $output));
            }
        }
    }
}
