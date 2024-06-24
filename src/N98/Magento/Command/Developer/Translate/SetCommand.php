<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Exception;
use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set translations command
 *
 * @package NN98\Magento\Command\Developer\Translate
 */
class SetCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_STRING = 'string';

    public const COMMAND_ARGUMENT_TRANSLATE = 'translate';

    public const COMMAND_ARGUMENT_STORE = 'store';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:translate:set';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Adds a translation to core_translate table. <comment>Globally for locale</comment>';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_STRING,
                InputArgument::REQUIRED,
                'String to translate'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_TRANSLATE,
                InputArgument::REQUIRED,
                'Translated string'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_STORE,
                InputArgument::OPTIONAL
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parameterHelper = $this->getParameterHelper();

        /** @var Mage_Core_Model_Store $store */
        $store = $parameterHelper->askStore($input, $output);

        /** @var string $locale */
        $locale = Mage::getStoreConfig('general/locale/code', $store->getId());

        /** @var string $string */
        $string = $input->getArgument(self::COMMAND_ARGUMENT_STRING);

        /** @var string $translate */
        $translate = $input->getArgument(self::COMMAND_ARGUMENT_TRANSLATE);

        $resource = Mage::getResourceModel('core/translate_string');
        $resource->saveTranslate(
            $string,
            $translate,
            $locale,
            $store->getId()
        );

        $output->writeln(
            sprintf(
                'Translated (<info>%s</info>): <comment>%s</comment> => <comment>%s</comment>',
                $locale,
                $string,
                $translate
            )
        );

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());

        return Command::SUCCESS;
    }
}
