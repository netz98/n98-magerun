<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use N98\Magento\Command\AbstractCommand;

/**
 * Class AbstractCustomerCommand
 *
 * @package N98\Magento\Command\Customer
 */
abstract class AbstractCustomerCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_EMAIL = 'email';

    public const COMMAND_ARGUMENT_PASSWORD = 'password';

    public const COMMAND_ARGUMENT_WEBSITE = 'website';
}
