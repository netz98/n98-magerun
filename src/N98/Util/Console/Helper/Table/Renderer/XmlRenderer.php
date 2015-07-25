<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use DOMDocument;
use Symfony\Component\Console\Output\OutputInterface;

class XmlRenderer implements RendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(OutputInterface $output, array $rows)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $rootXml = $dom->createElement('table');
        $dom->appendChild($rootXml);

        foreach ($rows as $row) {
            $rowXml = $dom->createElement('row');
            foreach ($row as $key => $value) {
                $name     = preg_replace("/[^A-Za-z0-9]/u", '_', $key);
                $filtered = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
                $element = $dom->createElement($name, $filtered);
                if (!$element) {
                    $element = $dom->createComment(
                        sprintf(
                            "Error: Unable to create element %s from key %s with value %s (filtered: %s)",
                            var_export($name, true), var_export($key, true), var_export($value, true),
                            var_export($filtered, true)
                        )
                    );
                }
                $rowXml->appendChild($element);
            }
            $rootXml->appendChild($rowXml);
        }

        $output->writeln($dom->saveXML());
    }
}
