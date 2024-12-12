<?php

declare(strict_types=1);

namespace N98\Util\Markdown;

/**
 * Class VersionFilePrinter
 *
 * @package N98\Util\Markdown
 */
class VersionFilePrinter
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function printFromVersion(string $startVersion): string
    {
        $contentToReturn = '';

        $lines = preg_split("/((\r?\n)|(\r\n?))/", $this->content);
        if ($lines) {
            foreach ($lines as $line) {
                if ($line === $startVersion) {
                    break;
                }

                $contentToReturn .= $line . "\n";
            }
        }

        return trim($contentToReturn) . "\n";
    }
}
