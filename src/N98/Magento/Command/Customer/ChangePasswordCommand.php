<?php

namespace N98\Magento\Command\Customer;

use Exception;
use N98\Util\Console\Helper\ParameterHelper;
use RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Change customer password command
 *
 * @package N98\Magento\Command\Customer
 */
class ChangePasswordCommand extends AbstractCustomerCommand
{
    protected function configure()
    {
        $this
            ->setName('customer:change-password')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website of the customer')
            ->setDescription('Changes the password of a customer.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
Website parameter must only be given if more than one websites are available.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return 0;
        }

        // Password
        if (($password = $input->getArgument('password')) == null) {
            /* @var QuestionHelper $dialog */
            $dialog = $this->getHelper('question');
            $question = new Question('<question>Password:</question> ');
            $question->setHidden(true);
            $password = $dialog->ask($input, $output, $question);
        }

        /** @var ParameterHelper $parameterHelper */
        $parameterHelper = $this->getHelper('parameter');

        $email = $parameterHelper->askEmail($input, $output);
        $website = $parameterHelper->askWebsite($input, $output);

        $customer = $this->getCustomerModel()
            ->setWebsiteId($website->getId())
            ->loadByEmail($email);
        if ($customer->getId() <= 0) {
            $output->writeln('<error>Customer was not found</error>');
            return 0;
        }

        try {
            $result = $customer->validate();
            if (is_array($result)) {
                throw new RuntimeException(implode(PHP_EOL, $result));
            }
            $customer->setPassword($password);
            $customer->save();
            $output->writeln('<info>Password successfully changed</info>');
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return 0;
    }
}
