<?php

namespace N98\Magento\Command\Eav\Attribute;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\RuntimeException;

class ViewCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
      $this
          ->setName('eav:attribute:view')
          ->setDescription('View informations about an EAV attribute')
      ;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            throw new \RuntimeException('Currently not implemented');
        }
    }
}