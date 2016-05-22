<?php

namespace N98\Magento\Command\Developer\Translate;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractMagentoCommand
{
    protected function configure()
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $store = $this->getHelper('parameter')->askStore($input, $output);

        $locale = \Mage::getStoreConfig('general/locale/code', $store->getId());

        /* @var $store \Mage_Core_Model_Store */
        $resource = \Mage::getResourceModel('core/translate_string');
        $resource->saveTranslate(
            $input->getArgument('string'),
            $input->getArgument('translate'),
            $locale,
            $store->getId()
        );

        $output->writeln(
            sprintf(
                'Translated (<info>%s</info>): <comment>%s</comment> => <comment>%s</comment>',
                $locale,
                $input->getArgument('string'),
                $input->getArgument('translate')
            )
        );

        $input = new StringInput('cache:flush');
        $this->getApplication()->run($input, new NullOutput());
    }
}
