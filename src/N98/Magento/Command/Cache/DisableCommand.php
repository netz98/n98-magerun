<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableCommand extends AbstractCacheCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:disable';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Disables caches.';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        $this->initMagento();

        $codeArgument = BinaryString::trimExplodeEmpty(',', $input->getArgument(self::COMMAND_ARGUMENT_CODE));
        $this->saveCacheStatus($codeArgument, false);

        if (empty($codeArgument)) {
            $this->_getCacheModel()->flush();
        } else {
            foreach ($codeArgument as $type) {
                $this->_getCacheModel()->cleanType($type);
            }
        }

        if (count($codeArgument) > 0) {
            foreach ($codeArgument as $code) {
                $output->writeln('<info>Cache <comment>' . $code . '</comment> disabled</info>');
            }
        } else {
            $output->writeln('<info>Caches disabled</info>');
        }
        return 0;
    }
}
