<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use DOMDocument;
use DOMElement;
use DOMComment;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class XmlRenderer implements RendererInterface
{
    const NAME_ROOT = 'table';
    const NAME_ROW  = 'row';

    /**
     * {@inheritdoc}
     */
    public function render(OutputInterface $output, array $rows)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $table = $dom->createElement(self::NAME_ROOT);
        if (!$table) {
            throw new UnexpectedValueException('Failed to create new root dom element');
        }
        $table = $dom->appendChild($table);

        foreach ($rows as $row) {
            $rowNode = $this->appendRow($dom, $table);
            foreach ($row as $key => $value) {
                $element = $this->createField($dom, $key, $value);
                $rowNode->appendChild($element);
            }
        }

        $output->writeln($dom->saveXML());
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement  $parent
     *
     * @return DOMElement|\DOMNode
     */
    private function appendRow(DOMDocument $dom, DOMElement $parent)
    {
        $node = $dom->createElement(self::NAME_ROW);
        if (!$node) {
            throw new UnexpectedValueException('Failed to create new row dom element.');
        }

        $node = $parent->appendChild($node);

        return $node;
    }

    /**
     * create a DOMElement containing the data or a DOMComment with an error message in case the
     * creation failed.
     *
     * @param DOMDocument $dom
     * @param string      $key
     * @param string      $value
     *
     * @return DOMComment|DOMElement
     */
    private function createField(DOMDocument $dom, $key, $value)
    {
        $name     = preg_replace("/[^A-Za-z0-9]/u", '_', $key);
        $filtered = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        $node = $dom->createElement($name, $filtered);
        if ($node) {
            return $node;
        }

        $data = sprintf(
            "Error: Unable to create element %s from key %s with value %s (filtered: %s)",
            var_export($name, true), var_export($key, true), var_export($value, true),
            var_export($filtered, true)
        );
        $node = $dom->createComment($data);
        if ($node) {
            return $node;
        }

        throw new UnexpectedValueException('Unable to create a new dom node: ' . $data);
    }
}
