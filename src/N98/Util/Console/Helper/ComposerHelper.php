<?php

namespace N98\Util\Console\Helper;

use N98\Util\BinaryString;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class ComposerHelper
 * @package N98\Util\Console\Helper
 */
class ComposerHelper extends AbstractHelper implements InputAwareInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @param array $composerArgs
     * @param bool $silent
     * @return string
     */
    public function run(array $composerArgs, $silent = false)
    {
        $commandArgs = array_merge([$this->getBinPath()], $composerArgs);

        $process = new Process($commandArgs);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($silent) {
            if ($silent) {
                return;
            }

            echo $buffer; // find a solution to use OutputInterface
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /**
     * Returns the composer config key -> Composer passed json data
     *
     * @param string $key
     * @param bool $useGlobalConfig
     * @return string|object
     */
    public function getConfigValue($key, $useGlobalConfig = true)
    {
        $jsonCode = '';
        $commandArgs = ['-q'];

        if ($useGlobalConfig) {
            $commandArgs[] = 'global';
        }

        $commandArgs[] = 'config';
        $commandArgs[] = $key;

        try {
            $composerOutput = $this->run($commandArgs, true);

            $lines = explode(PHP_EOL, $composerOutput);

            foreach ($lines as $line) {
                if (BinaryString::startsWith($line, 'Changed current directory to')) {
                    continue;
                }

                $jsonCode .= $line;
            }
        } catch (\Exception $e) {
            $jsonCode = 'false';
        }

        return \json_decode($jsonCode);
    }

    /**
     * @param string $key
     * @param array $values
     * @param bool $useGlobalConfig
     * @return string
     */
    public function setConfigValue($key, $values, $useGlobalConfig = true)
    {
        $commandArgs = [];
        if ($useGlobalConfig) {
            $commandArgs[] = 'global';
        }

        $commandArgs[] = 'config';
        $commandArgs[] = $key;
        $commandArgs = array_merge($commandArgs, $values);

        return $this->run($commandArgs, false);
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->getBinPath() !== '';
    }

    /**
     * Returns the path to composer bin
     *
     * @return string
     */
    public function getBinPath()
    {
        $composerBin = '';

        if (OperatingSystem::isProgramInstalled('composer.phar')) {
            $composerBin = 'composer.phar';
        } elseif (OperatingSystem::isProgramInstalled('composer')) {
            $composerBin = 'composer';
        }

        return $composerBin;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'composer';
    }

    /**
     * Sets the Console Input.
     *
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }
}
