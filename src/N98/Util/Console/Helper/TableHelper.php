<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\Table\Renderer\RendererInterface;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Text Table Helper
 *
 * Based on draw_text_table by Paul Maunders
 * Available at http://www.pyrosoft.co.uk/blog/2007/07/01/php-array-to-text-table-function/
 *
 * @package N98\Util\Console\Helper
 *
 * @author Timothy Anido <xanido@gmail.com> */
class TableHelper extends AbstractHelper
{
    protected string $format;

    protected array $headers = [];

    protected array $rows = [];

    public function setFormat(string $format): TableHelper
    {
        $this->format = $format;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string|null $format [optional]
     */
    public function renderByFormat(OutputInterface $output, array $rows, ?string $format = null): void
    {
        $rendererFactory = new RendererFactory();
        $renderer = $rendererFactory->create($format);

        if ($renderer instanceof RendererInterface) {
            foreach ($rows as &$row) {
                if ($this->headers !== []) {
                    $row = array_combine($this->headers, $row);
                }
            }

            $renderer->render($output, $rows);
        } else {
            $this->setRows($rows);
            $this->render($output);
        }
    }

    /**
     * Takes a two-dimensional tabular array with headers as keys in the first row and outputs an ascii table
     *
     * @deprecated since 1.98.0 use original Symfony table instead.
     * @param array<int, mixed> $rows
     */
    public function write(OutputInterface $output, array $rows): void
    {
        $this->setHeaders(array_keys($rows[0]));
        $this->setRows($rows);
        $this->render($output);
    }

    public function render(OutputInterface $output, array $rows = []): void
    {
        if ($rows === []) {
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
    public function getName(): string
    {
        return 'table';
    }

    public function setRows(array $rows): TableHelper
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * @param array<int|string> $headers
     */
    public function setHeaders(array $headers): TableHelper
    {
        $this->headers = array_values($headers);
        return $this;
    }
}
