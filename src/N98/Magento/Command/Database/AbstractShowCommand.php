<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Util\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractShowCommand
 *
 * @package N98\Magento\Command\Database
 */
abstract class AbstractShowCommand extends AbstractDatabaseCommand
{
    protected string $showMethod = 'getGlobalVariables';

    protected InputInterface $_input;

    protected OutputInterface $_output;

    protected array $_importantVars = [];

    /**
     * Key = variable name => value method name in this class
     */
    protected array $_specialFormat = [];

    /**
     * Contains all variables
     */
    protected array $_allVariables = [];

    protected function configure(): void
    {
        $this
            ->addArgument(
                'search',
                InputArgument::OPTIONAL,
                'Only output variables of specified name. The wildcard % is supported!',
            )
            ->addFormatOption()
            ->addOption(
                'rounding',
                null,
                InputOption::VALUE_OPTIONAL,
                'Amount of decimals to display. If -1 then disabled',
                0,
            )
            ->addOption(
                'no-description',
                null,
                InputOption::VALUE_NONE,
                'Disable description',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->initVariables($this->_input->getArgument('search'));
        $outputVars = $this->_allVariables;
        if (null === $this->_input->getArgument('search')) {
            $outputVars = array_intersect_key($this->_allVariables, $this->_importantVars);
        }

        $outputVars = $this->formatVariables($outputVars);
        $hasDescription = isset($this->_importantVars[array_key_first($this->_importantVars)]['desc']) &&
            false === $this->_input->getOption('no-description');
        $header = ['Variable Name', 'Value'];
        if ($hasDescription) {
            $header[] = 'Description';
        }

        $this->renderTable($header, $this->generateRows($outputVars, $hasDescription));

        return Command::SUCCESS;
    }

    protected function generateRows(array $outputVars, bool $hasDescription): array
    {
        $rows = [];
        $i = 0;
        foreach ($outputVars as $variableName => $variableValue) {
            $rows[$i] = [$variableName, $variableValue];
            if (isset($this->_importantVars[$variableName]['desc']) && $hasDescription
            ) {
                $rows[$i][] = $this->formatDesc($this->_importantVars[$variableName]['desc']);
            }

            ++$i;
        }

        // when searching no every variable has a description so fill the missing ones with blanks
        if (false === $hasDescription) {
            return $rows;
        }

        foreach ($rows as $k => $r) {
            if (2 === count($r)) {
                $rows[$k] = $this->getVariableDescription($r);
            }
        }

        return $rows;
    }

    /**
     * Extend or modify this method to add descriptions to other variables
     */
    protected function getVariableDescription(array $row): array
    {
        $row[] = '';
        return $row;
    }

    /**
     * Formats the description
     */
    protected function formatDesc(string $desc): string
    {
        $desc = preg_replace('~\s+~', ' ', $desc);
        return wordwrap($desc);
    }

    protected function renderTable(array $header, array $rows): void
    {
        $tableHelper = $this->getTableHelper();
        $tableHelper->setHeaders($header)
            ->renderByFormat($this->_output, $rows, $this->_input->getOption('format'));
    }

    protected function initVariables(?string $variable = null): void
    {
        $databaseHelper = $this->getDatabaseHelper();
        $this->_allVariables = $databaseHelper->{$this->showMethod}($variable);
    }

    protected function formatVariables(array $vars): array
    {
        $isStandardFormat = $this->_input->getOption('format') === null;
        $rounding = (int) $this->_input->getOption('rounding');
        if ($rounding > -1) {
            foreach ($vars as $k => &$v) {
                $v = trim($v);
                if ($this->allowRounding($k)) {
                    $v = Filesystem::humanFileSize((int) $v, $rounding);
                }

                if (isset($this->_specialFormat[$k])) {
                    $formatter = $this->_specialFormat[$k];
                    if (is_string($formatter) && method_exists($this, $formatter)) {
                        $formatter = [$this, $formatter];
                    }

                    $v = call_user_func($formatter, $v);
                }
            }

            unset($v);
        }

        if ($isStandardFormat) {
            // align=right
            $maxWidth = $this->getMaxValueWidth($vars);
            foreach ($vars as &$var) {
                $var = str_pad($var, $maxWidth, ' ', STR_PAD_LEFT);
            }
        }

        return $vars;
    }

    protected function getMaxValueWidth(array $vars): int
    {
        $maxWidth = 0;
        foreach ($vars as $var) {
            $l = strlen($var);
            if ($l > $maxWidth) {
                $maxWidth = $l;
            }
        }

        return $maxWidth;
    }

    abstract protected function allowRounding(string $name): bool;
}
