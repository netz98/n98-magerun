<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use Exception;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function json_decode;

/**
 * Class ComposerHelper
 *
 * @package N98\Util\Console\Helper
 */
class ComposerHelper extends AbstractHelper implements InputAwareInterface
{
    public function run(array $composerArgs, bool $silent = false): string
    {
        $commandArgs = array_merge([$this->getBinPath()], $composerArgs);

        $process = new Process($commandArgs);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($silent): void {
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
     * @return string|object
     */
    public function getConfigValue(string $key, bool $useGlobalConfig = true)
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
                if (str_starts_with($line, 'Changed current directory to')) {
                    continue;
                }

                $jsonCode .= $line;
            }
        } catch (Exception $exception) {
            $jsonCode = 'false';
        }

        return json_decode($jsonCode);
    }

    public function setConfigValue(string $key, array $values, bool $useGlobalConfig = true): string
    {
        $commandArgs = [];
        if ($useGlobalConfig) {
            $commandArgs[] = 'global';
        }

        $commandArgs[] = 'config';
        $commandArgs[] = $key;
        $commandArgs = array_merge($commandArgs, $values);

        return $this->run($commandArgs);
    }

    public function isInstalled(): bool
    {
        return $this->getBinPath() !== '';
    }

    /**
     * Returns the path to composer bin
     */
    public function getBinPath(): string
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
     * @api
     */
    public function getName(): string
    {
        return 'composer';
    }

    /**
     * Sets the Console Input.
     *
     * @return void
     */
    public function setInput(InputInterface $input) {}
}
