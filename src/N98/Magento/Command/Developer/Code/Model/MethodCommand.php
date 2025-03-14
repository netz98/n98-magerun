<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Code\Model;

use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Abstract;
use N98\Magento\Command\AbstractMagentoCommand;
use PDO;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create method annotation command
 *
 * @package N98\Magento\Command\Developer\Code\Model
 */
class MethodCommand extends AbstractMagentoCommand
{
    protected InputInterface $_input;

    protected OutputInterface $_output;

    /**
     * @var Mage_Core_Model_Abstract|false
     */
    protected $_mageModel;

    protected ?string $_mageModelTable;

    /**
     * @var string|false
     */
    protected $_fileName = '';

    /**
     * @see initTableColumns
     */
    protected array $_tableColumns = [];

    protected function configure(): void
    {
        $this
            ->setName('dev:code:model:method')
            ->addArgument('modelName', InputOption::VALUE_REQUIRED, 'Model Name namespace/modelName')
            ->setDescription(
                'Code annotations: Reads the columns from a table and writes the getter and setter methods into the ' .
                'class file for @methods.',
            );
    }

    /**
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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
        $this->_output->writeln('Wrote getter and setter @methods into file: ' . $this->_fileName);

        return Command::SUCCESS;
    }

    protected function writeToClassFile(): void
    {
        if ($this->_fileName === false) {
            throw new RuntimeException('No filename set');
        }

        $file = file($this->_fileName);
        if ($file === false) {
            throw new RuntimeException('No filename set');
        }

        $modelFileContent   = implode('', $file);
        $fileParts          = preg_split('~(\s+)(class)(\s+)([a-z0-9_]+)~i', $modelFileContent, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($fileParts) {
            foreach ($fileParts as $index => $part) {
                if (strtolower($part) === 'class') {
                    $fileParts[$index] = $this->generateComment() . $part;
                    break;
                }
            }

            $written = file_put_contents($this->_fileName, implode('', $fileParts));

            if ($written === false) {
                throw new RuntimeException('Cannot write to file: ' . $this->_fileName);
            }
        }
    }

    protected function generateComment(): string
    {
        return PHP_EOL . '/**' . PHP_EOL . implode(PHP_EOL, $this->getGetterSetter()) . PHP_EOL . ' */' . PHP_EOL;
    }

    protected function getGetterSetter(): array
    {
        $getterSetter = [];

        if (!$this->_mageModel) {
            return $getterSetter;
        }

        $modelClassName = get_class($this->_mageModel);

        foreach ($this->_tableColumns as $colName => $colProp) {
            $getterSetter[] = sprintf(
                ' * @method %s get%s()',
                $this->getColumnType($colProp['Type']),
                $this->camelize($colName),
            );
            $getterSetter[] = sprintf(
                ' * @method %s set%s(%s $value)',
                $modelClassName,
                $this->camelize($colName),
                $this->getColumnType($colProp['Type']),
            );
        }

        return $getterSetter;
    }

    protected function camelize(string $name): string
    {
        return uc_words($name, '');
    }

    /**
     * Mapping method to transform MySQL column types into PHP types
     */
    protected function getColumnType(string $columnType): string
    {
        $cte = explode('(', $columnType);
        $columnType = strtolower($cte[0]);
        $typeMapper = [
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
        ];

        return $typeMapper[$columnType] ?? '';
    }

    /**
     * helper method to fill _tableColumns array
     *
     * @see _tableColumns
     */
    protected function initTableColumns(): void
    {
        $databaseHelper = $this->getDatabaseHelper();
        $pdo            = $databaseHelper->getConnection($this->_output);
        $stmt           = $pdo->query('SHOW COLUMNS FROM ' . $this->_mageModelTable, PDO::FETCH_ASSOC);

        if ($stmt) {
            foreach ($stmt as $row) {
                $this->_tableColumns[$row['Field']] = $row;
            }
        }

        if ($this->_tableColumns === []) {
            throw new InvalidArgumentException('No columns found in table: ' . $this->_mageModelTable);
        }
    }

    /**
     * @return string|false
     */
    protected function searchFullPath(string $filename)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
            if (@file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return false;
    }

    protected function checkClassFileName(): void
    {
        if ($this->_mageModel === false) {
            throw new InvalidArgumentException('No model set');
        }

        $fileName = str_replace(
            ' ',
            DIRECTORY_SEPARATOR,
            ucwords(str_replace('_', ' ', get_class($this->_mageModel))),
        ) . '.php';
        $this->_fileName = $this->searchFullPath($fileName);

        if ($this->_fileName === false) {
            throw new InvalidArgumentException('No file set');
        }
    }

    protected function checkModel(): void
    {
        $modelName = $this->_input->getArgument('modelName');

        $this->_mageModel = Mage::getModel($modelName);
        if (!$this->_mageModel) {
            throw new InvalidArgumentException('Model ' . $modelName . ' not found!');
        }

        $this->_mageModelTable = $this->_mageModel->getResource()
            ? $this->_mageModel->getResource()->getMainTable() : null;
        if (!isset($this->_mageModelTable) || ($this->_mageModelTable === '' || $this->_mageModelTable === '0')) {
            throw new InvalidArgumentException(
                'Cannot find main table of model ' . $modelName,
            );
        }
    }
}
