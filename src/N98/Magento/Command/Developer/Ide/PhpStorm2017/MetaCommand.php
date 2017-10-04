<?php

namespace N98\Magento\Command\Developer\Ide\PhpStorm2017;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Magento\Command\Developer\Ide\PhpStorm\MetaCommand as BaseMetaCommand;

class MetaCommand extends BaseMetaCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:ide:phpstorm:meta')
            ->addOption('stdout', null, InputOption::VALUE_NONE, 'Print to stdout instead of files in .phpstorm.meta.php')
            ->setDescription('Generates meta data file for PhpStorm auto completion')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $classMaps
     */
    protected function writeToOutput(InputInterface $input, OutputInterface $output, $classMaps)
    {
        $baseMap = <<<PHP
<?php
namespace PHPSTORM_META {
    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    /** @noinspection PhpLanguageLevelInspection */
    \$STATIC_METHOD_TYPES = [
PHP;
        $baseMap .= "\n";
        foreach ($this->groupFactories as $group => $methods) {
            $map = $baseMap;
            foreach ($methods as $method) {
                $map .= "        " . $method . "('') => [\n";
                foreach ($classMaps[$group] as $classPrefix => $class) {
                    if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                        $map .= "            '$classPrefix' instanceof \\$class,\n";
                    } else {
                        $output->writeln('<warning>Invalid class name <comment>'.$class.'</comment> ignored</warning>');
                    }
                }
                $map .= "        ], \n";
            }
            $map .= <<<PHP
    ];
}
PHP;
            if ($input->getOption('stdout')) {
                $output->writeln($map);
            } else {
                $metaPath = $this->_magentoRootFolder . '/.phpstorm.meta.php';
                if (is_file($metaPath)) {
                    if (\unlink($metaPath)) {
                        $output->writeln('<info>Deprecated file <comment>.phpstorm.meta.php</comment> removed</info>');
                    }
                }
                if (!is_dir($metaPath)) {
                    if (\mkdir($metaPath)) {
                        $output->writeln('<info>Directory <comment>.phpstorm.meta.php</comment> created</info>');
                    }
                }
                $group = str_replace(array(' ', '/'), '_', $group);
                if (\file_put_contents($this->_magentoRootFolder . '/.phpstorm.meta.php/magento_'.$group.'.meta.php', $map)) {
                    $output->writeln('<info>File <comment>.phpstorm.meta.php/magento_'.$group.'.meta.php</comment> generated</info>');
                }
            }
        }
    }
}
