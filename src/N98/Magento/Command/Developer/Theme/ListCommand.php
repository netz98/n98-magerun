<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:theme:list')
            ->setDescription('Lists all available themes');
    }

    /**
     * @param \Symfony\Component\Console\Input\\Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\\Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $packages = $this->getDesignPackageModel()->getThemeList();
            $table = array();
            foreach ($packages as $package => $themes) {
                foreach ($themes as $theme) {
                    $table[] = array(
                        'name' => $package . '/' . $theme
                    );
                }
            }

            $this->getHelper('table')->write($output, $table);
        }
    }

    /**
     * @return Mage_Core_Model_Design_Package
     */
    protected function getDesignPackageModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Core_Model_Design_Package');
        }

        return \Mage::getModel('core/design_package');
    }
}