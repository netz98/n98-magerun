<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper\Table\Renderer;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class XmlRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class XmlRenderer implements RendererInterface
{
    public const NAME_ROOT = 'table';

    public const NAME_ROW = 'row';

    private array $headers = [];

    public function render(OutputInterface $output, array $rows): void
    {
        $domDocument = new DOMDocument('1.0', 'UTF-8');
        $domDocument->formatOutput = true;

        $rows && $this->setHeadersFrom($rows);

        $table = $domDocument->createElement(self::NAME_ROOT);
        if ($table) {
            $table = $domDocument->appendChild($table);
            $this->appendHeaders($table, $this->headers);
            $this->appendRows($table, $rows);
        }

        $xml = $domDocument->saveXML($domDocument, LIBXML_NOEMPTYTAG);
        if ($xml) {
            $output->write($xml, false, $output::OUTPUT_RAW);
        }
    }

    private function appendRows(DOMNode $domNode, array $rows): void
    {
        $doc = $domNode->ownerDocument;

        if ($rows === []) {
            $domNode->appendChild($doc->createComment('intentionally left blank, the table is empty'));

            return;
        }

        foreach ($rows as $fields) {
            /** @var DOMElement $row */
            $row = $domNode->appendChild($doc->createElement(self::NAME_ROW));
            $this->appendRowFields($row, $fields);
        }
    }

    private function appendRowFields(DOMElement $domElement, array $fields): void
    {
        $index = 0;
        foreach ($fields as $key => $value) {
            $header     = $this->getHeader($index++, $key);
            $element    = $this->createField($domElement->ownerDocument, (string) $header, (string) $value);
            $domElement->appendChild($element);
        }
    }

    private function appendHeaders(DOMNode $domNode, ?array $headers = null): void
    {
        if ($headers === null || $headers === []) {
            return;
        }

        $doc = $domNode->ownerDocument;

        $domNode = $domNode->appendChild($doc->createElement('headers'));

        foreach ($headers as $header) {
            $domNode->appendChild($doc->createElement('header', (string) $header));
        }
    }

    /**
     * create a DOMElement containing the data
     */
    private function createField(DOMDocument $domDocument, string $key, string $value): DOMElement
    {
        $name = $this->getName($key);

        $base64 = in_array(preg_match('//u', $value), [0, false], true) || preg_match('/[\x0-\x8\xB-\xC\xE-\x1F]/', $value);

        $domElement = $domDocument->createElement($name, $base64 ? base64_encode($value) : $value);

        if ($base64) {
            $domElement->setAttribute('encoding', 'base64');
        }

        return $domElement;
    }

    /**
     * @throws DOMException if no valid XML Name can be generated
     * @throws RuntimeException if character encoding is not US-ASCII or UTF-8
     */
    private function getName(string $string): string
    {
        $name = preg_replace('/[^a-z0-9]/ui', '_', $string);
        if (null === $name) {
            throw new RuntimeException(
                sprintf(
                    'Encoding error, only US-ASCII and UTF-8 supported, can not process %s',
                    var_export($string, true),
                ),
            );
        }

        try {
            new DOMElement($name);
        } catch (DOMException $domException) {
            throw new DOMException(sprintf('Invalid name %s', var_export($name, true)), $domException->getCode(), $domException);
        }

        return $name;
    }

    /**
     * @param string|int|null $default
     * @return string|int|null
     */
    private function getHeader(int $index, $default = null)
    {
        if (!isset($this->headers[$index])) {
            return $default;
        }

        return $this->headers[$index];
    }

    private function setHeadersFrom(array $rows): void
    {
        $first = reset($rows);

        if (is_array($first)) {
            $this->headers = array_keys($first);
        }
    }
}
