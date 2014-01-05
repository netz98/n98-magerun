<?php

namespace N98\Util\Console\Helper;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\Table\Renderer\RendererInterface;
use Symfony\Component\Console\Helper\TableHelper as BaseTableHelper;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Text Table Helper
 * @author Timothy Anido <xanido@gmail.com>
 *
 * Based on draw_text_table by Paul Maunders
 * Available at http://www.pyrosoft.co.uk/blog/2007/07/01/php-array-to-text-table-function/
 */
class TableHelper extends BaseTableHelper
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @var array
     */
    protected $headers = array();

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
     * @param OutputInterface $output
     */
    public function render(OutputInterface $output)
    {
        if ($this->format == 'csv') {
            $this->renderCsv();
        } else {
            parent::render($output);
        }
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_values($headers);
        parent::setHeaders($headers);

        return $this;
    }

    /**
     * @param OutputInterface $outputInterface
     * @param array $rows
     * @param string $format
     */
    public function renderByFormat(OutputInterface $outputInterface, array $rows, $format = '')
    {
        $rendererFactory = new RendererFactory();
        $renderer = $rendererFactory->create($format);
        if ($renderer && $renderer instanceof RendererInterface) {
            foreach ($rows as &$row) {
                $row = array_combine($this->headers, $row);
            }
            $renderer->render($outputInterface, $rows);
        } else {
            $this->setRows($rows);
            $this->render($outputInterface);
        }
    }

    /**
     * Takes a 2 dimensional tabular array (or iterable object) and outputs an ascii table
     *
     * @deprecated Use original Symfony table helper
     * @param  OutputInterface $output
     * @param  array           $table
     * @param  int             $crop    Maximum column width
     * @param  boolean         $rowKeys Display the keys as first column
     */
    public function write(OutputInterface $output, $table, $crop = null, $rowKeys = false)
    {
        $this->setHeaders(array_keys($table[0]));
        $this->setRows($table);

        return $this->render($output);
    }
}