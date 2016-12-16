<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use DOMDocument;
use DOMElement;
use DOMException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class XmlRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class XmlRenderer implements RendererInterface
{
    const NAME_ROOT = 'table';
    const NAME_ROW = 'row';

    private $headers;

    /**
     * {@inheritdoc}
     */
    public function render(OutputInterface $output, array $rows)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rows && $this->setHeadersFrom($rows);

        $table = $dom->createElement(self::NAME_ROOT);

        /** @var DOMElement $table */
        $table = $dom->appendChild($table);

        $this->appendHeaders($table, $this->headers);
        $this->appendRows($table, $rows);

        /** @var $output \Symfony\Component\Console\Output\StreamOutput */
        $output->write($dom->saveXML($dom, LIBXML_NOEMPTYTAG), false, $output::OUTPUT_RAW);
    }

    private function appendRows(DOMElement $parent, array $rows)
    {
        $doc = $parent->ownerDocument;

        if (!$rows) {
            $parent->appendChild($doc->createComment('intentionally left blank, the table is empty'));

            return;
        }

        foreach ($rows as $fields) {
            /** @var DOMElement $row */
            $row = $parent->appendChild($doc->createElement(self::NAME_ROW));
            $this->appendRowFields($row, $fields);
        }
    }

    /**
     * @param DOMElement $row
     * @param array      $fields
     */
    private function appendRowFields(DOMElement $row, array $fields)
    {
        $index = 0;
        foreach ($fields as $key => $value) {
            $header = $this->getHeader($index++, $key);
            $element = $this->createField($row->ownerDocument, $header, $value);
            $row->appendChild($element);
        }
    }

    /**
     * @param DOMElement $parent
     * @param array      $headers
     */
    private function appendHeaders(DOMElement $parent, array $headers = null)
    {
        if (!$headers) {
            return;
        }

        $doc = $parent->ownerDocument;

        $parent = $parent->appendChild($doc->createElement('headers'));

        foreach ($headers as $header) {
            $parent->appendChild($doc->createElement('header', $header));
        }
    }

    /**
     * create a DOMElement containing the data
     *
     * @param DOMDocument $doc
     * @param string      $key
     * @param string      $value
     *
     * @return DOMElement
     */
    private function createField(DOMDocument $doc, $key, $value)
    {
        $name = $this->getName($key);

        $base64 = !preg_match('//u', $value) || preg_match('/[\x0-\x8\xB-\xC\xE-\x1F]/', $value);

        $node = $doc->createElement($name, $base64 ? base64_encode($value) : $value);

        if ($base64) {
            $node->setAttribute('encoding', 'base64');
        }

        return $node;
    }

    /**
     * @param string $string
     *
     * @return string valid XML element name
     *
     * @throws DOMException if no valid XML Name can be generated
     * @throws RuntimeException if character encoding is not US-ASCII or UTF-8
     */
    private function getName($string)
    {
        $name = preg_replace("/[^a-z0-9]/ui", '_', $string);
        if (null === $name) {
            throw new RuntimeException(
                sprintf(
                    'Encoding error, only US-ASCII and UTF-8 supported, can not process %s',
                    var_export($string, true)
                )
            );
        }

        try {
            new DOMElement("$name");
        } catch (DOMException $e) {
            throw new DOMException(sprintf('Invalid name %s', var_export($name, true)));
        }

        return $name;
    }

    /**
     * @param int   $index zero-based
     * @param mixed $default
     *
     * @return string
     */
    private function getHeader($index, $default = null)
    {
        if (!isset($this->headers[$index])) {
            return $default;
        }

        return $this->headers[$index];
    }

    /**
     * @param array $rows
     *
     * @return void
     */
    private function setHeadersFrom(array $rows)
    {
        $first = reset($rows);

        if (is_array($first)) {
            $this->headers = array_keys($first);
        }
    }
}
