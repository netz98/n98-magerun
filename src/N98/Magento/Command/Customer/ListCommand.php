<?php

namespace N98\Magento\Command\Customer;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:list')
            ->addArgument('search', InputArgument::OPTIONAL, 'Search query')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setDescription('Lists customers')
        ;

        $help = <<<HELP
List customers. The output is limited to 1000 (can be changed by overriding config).
If search parameter is given the customers are filtered (searchs in firstname, lastname and email).
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $config = $this->getCommandConfig();

        $collection = $this->getCustomerCollection();
        $collection->addAttributeToSelect(['entity_id', 'email', 'firstname', 'lastname', 'website_id']);

        if ($input->getArgument('search')) {
            $collection->addAttributeToFilter(
                [['attribute' => 'email', 'like' => '%' . $input->getArgument('search') . '%'], ['attribute' => 'firstname', 'like' => '%' . $input->getArgument('search') . '%'], ['attribute' => 'lastname', 'like' => '%' . $input->getArgument('search') . '%']]
            );
        }

        $collection->setPageSize($config['limit']);

        $table = [];
        foreach ($collection as $customer) {
            $table[] = [$customer->getId(), $customer->getEmail(), $customer->getFirstname(), $customer->getLastname(), $this->_getWebsiteCodeById($customer->getwebsiteId())];
        }

        if (count($table) > 0) {
            /* @var TableHelper $tableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(['id', 'email', 'firstname', 'lastname', 'website'])
                ->renderByFormat($output, $table, $input->getOption('format'));
        } else {
            $output->writeln('<comment>No customers found</comment>');
        }
        return 0;
    }
}
