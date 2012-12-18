<?php

namespace N98\Magento\Command\Dummy;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Cache\ClearCommand as ClearCacheCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;

class CustomerCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this->_rand = rand(0, 100);

        $this
            ->setName('dummy:customer')
            ->addOption('first', null, InputOption::VALUE_OPTIONAL, "First name")
            ->addOption('last', null, InputOption::VALUE_OPTIONAL, "Last name")
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, "E-mail")
            ->setHelp("By default will create random first name, last name, and email.  You can specify them though if you'd like with the above command-line parameters.")
            ->setDescription('Create a dummy customer')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;

        $this->detectMagento($output);
        $this->initMagento();
        $customer = \Mage::getModel('customer/customer')
            ->setFirstname($this->getFirstname())
            ->setLastname($this->getLastname())
            ->setEmail($this->getEmail())
            ->save();

        $output->writeln(sprintf("<info>Created dummy customer %s: %s %s (%s) </info>",
            $customer->getId(),
            $this->getFirstname(),
            $this->getLastname(),
            $this->getEmail()
        ));
    }

    public function getFirstname()
    {
        if ($this->_input->getOption('first')) {
            return $this->_input->getOption('first');
        }

        return 'Test';
    }

    public function getLastname()
    {
        if ($this->_input->getOption('last')) {
            return $this->_input->getOption('last');
        }

        return 'Last' . $this->_rand;
    }

    public function getEmail()
    {
        if ($this->_input->getOption('email')) {
            return $this->_input->getOption('email');
        }

        return 'test' . $this->_rand . '@example.com';
    }
}