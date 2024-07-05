<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Magento\Command\AbstractCommand;

/**
 * @package N98\Magento\Command\Cms\Block
 */
class AbstractCmsBlockCommand extends AbstractCommand
{
    /**
     * {@inheritDoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->detectMagento($output);
        $this->initMagento();
    }
}
