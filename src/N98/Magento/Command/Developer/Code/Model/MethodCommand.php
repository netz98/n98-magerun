<?php

namespace N98\Magento\Command\Developer\Code\Model;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MethodCommand extends AbstractMagentoCommand
{
    /**
     * @var InputInterface
     */
    protected $_input = null;

    /**
     * @var OutputInterface
     */
    protected $_output = null;

    /**
     * @var \Mage_Core_Model_Abstract
     */
    protected $_mageModel = null;

    /**
     * @var string
     */
    protected $_mageModelTable = null;

    /**
     * @var string
     */
    protected $_fileName = '';

    /**
     * @var array
     * @see initTableColumns
     */
    protected $_tableColumns = array();

    protected function configure()
    {
        $this
            ->setName('dev:code:model:method')
            ->addArgument('modelName', InputOption::VALUE_REQUIRED, 'Model Name namespace/modelName')
            ->setDescription(
                'Code annotations: Reads the columns from a table and writes the getter and setter methods into the ' .
                'class file for @methods.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->detectMagento($this->_output, true);
        if (false === $this->initMagento()) {
            throw new RuntimeException('Magento could not be loaded');
        }
        $this->checkModel();
        $this->checkClassFileName();
        $this->initTableColumns();
        $this->writeToClassFile();
        $this->_output->writeln("Wrote getter and setter @methods into file: " . $this->_fileName);
    }

    protected function writeToClassFile()
    {
        $modelFileContent = implode('', file($this->_fileName));
        $fileParts = preg_split('~(\s+)(class)(\s+)([a-z0-9_]+)~i', $modelFileContent, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($fileParts as $index => $part) {
            if (strtolower($part) === 'class') {
                $fileParts[$index] = $this->generateComment() . $part;
                break;
            }
        }
        $written = file_put_contents($this->_fileName, implode('', $fileParts));
        if (false === $written) {
            throw new RuntimeException("Cannot write to file: " . $this->_fileName);
        }
    }

    /**
     * @return string
     */
    protected function generateComment()
    {
        return PHP_EOL . '/**' . PHP_EOL . implode(PHP_EOL, $this->getGetterSetter()) . PHP_EOL . ' */' . PHP_EOL;
    }

    /**
     * @return array
     */
    protected function getGetterSetter()
    {
        $modelClassName = get_class($this->_mageModel);
        $getterSetter = array();
        foreach ($this->_tableColumns as $colName => $colProp) {
            $getterSetter[] = sprintf(' * @method %s get%s()', $this->getColumnType($colProp['Type']),
                $this->camelize($colName));
            $getterSetter[] = sprintf(' * @method %s set%s(%s $value)', $modelClassName, $this->camelize($colName),
                $this->getColumnType($colProp['Type']));
        }

        return $getterSetter;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function camelize($name)
    {
        return uc_words($name, '');
    }

    /**
     * Mapping method to transform MySQL column types into PHP types
     *
     * @param $columnType
     *
     * @return string
     */
    protected function getColumnType($columnType)
    {
        $cte = explode('(', $columnType);
        $columnType = strtolower($cte[0]);
        $typeMapper = array(
            'int'        => 'int',
            'tinyint'    => 'int',
            'smallint'   => 'int',
            'decimal'    => 'float',
            'float'      => 'float',
            'double'     => 'float',
            'real'       => 'float',
            'char'       => 'string',
            'varchar'    => 'string',
            'text'       => 'string',
            'tinytext'   => 'string',
            'mediumtext' => 'string',
            'longtext'   => 'string',
            'date'       => 'string',
            'datetime'   => 'string',
            'timestamp'  => 'string',
        );

        return isset($typeMapper[$columnType]) ? $typeMapper[$columnType] : '';
    }

    /**
     * helper method to fill _tableColumns array
     *
     * @see _tableColumns
     */
    protected function initTableColumns()
    {
        $dbHelper = $this->getHelper('database');
        /* @var $dbHelper \N98\Util\Console\Helper\DatabaseHelper */
        /** @var \PDO $connection */
        $connection = $dbHelper->getConnection($this->_output);
        $stmt = $connection->query('SHOW COLUMNS FROM ' . $this->_mageModelTable, \PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $this->_tableColumns[$row['Field']] = $row;
        }
        if (0 === count($this->_tableColumns)) {
            throw new InvalidArgumentException('No columns found in table: ' . $this->_mageModelTable);
        }
    }

    /**
     * @param string $filename
     *
     * @return string|false
     */
    protected function searchFullPath($filename)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
            if (true === @file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return false;
    }

    protected function checkClassFileName()
    {
        $fileName = str_replace(
            ' ',
            DIRECTORY_SEPARATOR,
            ucwords(str_replace('_', ' ', get_class($this->_mageModel)))
        ) . '.php';
        $this->_fileName = $this->searchFullPath($fileName);

        if (false === $this->_fileName) {
            throw new InvalidArgumentException('File not found: ' . $this->_fileName);
        }
    }

    protected function checkModel()
    {
        $this->_mageModel = \Mage::getModel($this->_input->getArgument('modelName'));
        if (true === empty($this->_mageModel)) {
            throw new InvalidArgumentException('Model ' . $this->_input->getArgument('modelName') . ' not found!');
        }

        $this->_mageModelTable = $this->_mageModel->getResource()
            ? $this->_mageModel->getResource()->getMainTable() : null;
        if (true === empty($this->_mageModelTable)) {
            throw new InvalidArgumentException(
                'Cannot find main table of model ' . $this->_input->getArgument('modelName')
            );
        }
    }
}
