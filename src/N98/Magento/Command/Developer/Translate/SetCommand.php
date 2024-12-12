<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Exception;
use Mage;
use Mage_Core_Model_Resource_Translate_String;
use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set translation command
 *
 * @package N98\Magento\Command\Developer\Translate
 */
class SetCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:translate:set')
            ->addArgument('string', InputArgument::REQUIRED, 'String to translate')
            ->addArgument('translate', InputArgument::REQUIRED, 'Translated string')
            ->addArgument('store', InputArgument::OPTIONAL)
            ->setDescription('Adds a translation to core_translate table. <comment>Globally for locale</comment>')
        ;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $parameterHelper = $this->getParameterHelper();

        /** @var Mage_Core_Model_Store $store */
        $store = $parameterHelper->askStore($input, $output);

        $locale = Mage::getStoreConfig('general/locale/code', $store->getId());

        /** @var Mage_Core_Model_Resource_Translate_String $resource */
        $resource = Mage::getResourceModel('core/translate_string');
        $resource->saveTranslate(
            $input->getArgument('string'),
            $input->getArgument('translate'),
            $locale,
            $store->getId(),
        );

        $output->writeln(
            sprintf(
                'Translated (<info>%s</info>): <comment>%s</comment> => <comment>%s</comment>',
                $locale,
                $input->getArgument('string'),
                $input->getArgument('translate'),
            ),
        );

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());
        return Command::SUCCESS;
    }
}
