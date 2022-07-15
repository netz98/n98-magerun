<?php

namespace N98\Util\Console\Helper;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\Table\Renderer\RendererInterface;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Text Table Helper
 * @author Timothy Anido <xanido@gmail.com>
 *
 * Based on draw_text_table by Paul Maunders
 * Available at http://www.pyrosoft.co.uk/blog/2007/07/01/php-array-to-text-table-function/
 */
class TableHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param OutputInterface $outputInterface
     * @param array $rows
     * @param string $format [optional]
     */
    public function renderByFormat(OutputInterface $outputInterface, array $rows, $format = null)
    {
        $rendererFactory = new RendererFactory();
        $renderer = $rendererFactory->create($format);

        if ($renderer && $renderer instanceof RendererInterface) {
            foreach ($rows as &$row) {
                if (!empty($this->headers)) {
                    $row = array_combine($this->headers, $row);
                }
            }

            $renderer->render($outputInterface, $rows);
        } else {
            $this->setRows($rows);
            $this->render($outputInterface);
        }
    }

    /**
     * Takes a two dimensional tabular array with headers as keys in the first row and outputs an ascii table
     *
     * @deprecated since 1.98.0 use original Symfony table instead.
     *
     * @param  OutputInterface $output
     * @param  array           $rows
     */
    public function write(OutputInterface $output, array $rows)
    {
        $this->setHeaders(array_keys($rows[0]));
        $this->setRows($rows);
        $this->render($output);
    }

    /**
     * @param OutputInterface $output
     * @param array $rows
     */
    public function render(OutputInterface $output, $rows = [])
    {
        if (empty($rows)) {
            $rows = $this->rows;
        }

        $baseTable = new Table($output);
        $baseTable->setRows($rows);
        $baseTable->setHeaders($this->headers);
        $baseTable->render();
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'table';
    }

    /**
     * @param array $rows
     * @return $this
     */
    public function setRows(array $rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @param array|string[] $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_values($headers);

        return $this;
    }
}
