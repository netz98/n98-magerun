<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * View cache command
 *
 * @package N98\Magento\Command\Cache
 */
class EnableCommand extends AbstractCacheCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:enable';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Enables caches.';

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
        $this->saveCacheStatus($codeArgument, true);

        if ($codeArgument !== []) {
            foreach ($codeArgument as $code) {
                $output->writeln('<info>Cache <comment>' . $code . '</comment> enabled</info>');
            }
        } else {
            $output->writeln('<info>Caches enabled</info>');
        }

        return 0;
    }
}
