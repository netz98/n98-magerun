<?php

declare(strict_types=1);

namespace N98\Util\Console;

use N98\Util\OperatingSystem;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

/**
 * Class Enabler
 *
 * Utility class to check console command requirements to be "enabled".
 *
 * @see \N98\Magento\Command\Database\DumpCommand::execute()
 *
 * @package N98\Util\Console
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class Enabler
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function functionExists(string $name): void
    {
        $this->assert(function_exists($name), sprintf('function "%s" is not available', $name));
    }

    public function operatingSystemIsNotWindows(): void
    {
        $this->assert(!OperatingSystem::isWindows(), 'operating system is windows');
    }

    /**
     * @param mixed $condition
     */
    private function assert($condition, string $message): void
    {
        if ($condition) {
            return;
        }

        throw new RuntimeException(
            sprintf('Command %s is not available because %s.', $this->command->getName(), $message),
        );
    }
}
