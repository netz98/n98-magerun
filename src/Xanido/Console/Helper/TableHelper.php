<?php

namespace Xanido\Console\Helper;

use Symfony\Component\Console\Helper\Helper as AbstractHelper;
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
     * Takes a 2 dimensional tabular array (or iterable object) and outputs an ascii table
     *
     * @param  OutputInterface $output
     * @param  array           $table
     * @param  int             $crop    Maximum column width
     * @param  boolean         $rowKeys Display the keys as first column
     */
    public function write(OutputInterface $output, $table, $crop = null, $rowKeys = false)
    {

        // Work out max lengths of each cell
        $longest_key = 0;
        foreach ($table AS $row_key => $row) {
            $cell_count = 0;
            foreach ($row AS $key => $cell) {
                $cell_length = strlen($cell);

                $cell_count++;
                if (!isset($cell_lengths[$key]) || $cell_length > $cell_lengths[$key]) {
                    $cell_lengths[$key] = $crop === null ? $cell_length : min(
                        $cell_length,
                        $crop
                    );
                }
            }
            $key_length = strlen($row_key);
            $longest_key = $key_length < $longest_key ? : $key_length;
        }

        // Build header bar
        $bar = '+';
        $header = '|';
        $i = 0;

        if ($rowKeys) {
            $bar .= str_pad('', $longest_key + 2, '-') . "+";
            $header .= ' <info>' . str_pad('', $longest_key, ' ', STR_PAD_RIGHT) . "</info> |";
        }

        foreach ($cell_lengths AS $fieldname => $length) {
            $i++;
            $bar .= str_pad('', $length + 2, '-') . "+";

            $name = $fieldname;
            if (strlen($name) > $length) {
                // crop long headings
                $name = substr($name, 0, max(0, $length - 1));
            }
            $header .= ' <info>' . str_pad($name, $length, ' ', STR_PAD_RIGHT) . "</info> |";

        }


        $output->writeln($bar);
        $output->writeln($header);
        $output->writeln($bar);

        // Draw rows
        foreach ($table AS $key => $row) {
            $out = '';
            $out .= "|";
            if ($rowKeys) {
                $out .= ' ' . str_pad(substr($key, 0, $longest_key), $longest_key, ' ', STR_PAD_RIGHT) . " |";
            }
            foreach ($row AS $key => $cell) {
                $out .= ' ' . str_pad(
                    substr($cell, 0, $cell_lengths[$key]),
                    $cell_lengths[$key],
                    ' ',
                    STR_PAD_RIGHT
                ) . " |";
            }
            $output->writeln($out);
        }

        $output->writeln($bar);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'table';
    }
}