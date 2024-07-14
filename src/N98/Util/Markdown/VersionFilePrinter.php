<?php

namespace N98\Util\Markdown;

/**
 * Class VersionFilePrinter
 *
 * @package N98\Util\Markdown
 */
class VersionFilePrinter
{
    /**
     * @var string
     */
    private $content;

    /**
     * @param string $content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * @param string $startVersion
     * @return string
     */
    public function printFromVersion($startVersion)
    {
        $contentToReturn = '';

        $lines = preg_split("/((\r?\n)|(\r\n?))/", $this->content);

        foreach ($lines as $line) {
            if ($line === $startVersion) {
                break;
            }

            $contentToReturn .= $line . "\n";
        }

        return trim($contentToReturn) . "\n";
    }
}
