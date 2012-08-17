<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallExtensionCommand extends AbstractConnectCommand
{
    protected function configure()
    {
        $this
            ->setName('extension:install')
            ->addArgument('package', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Packge to install')
            ->setDescription('Install magento-connect package')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $found = false;
        $alternatives = array();
        $extensions = $this->callMageScript($input, $output, 'list-available');
        $searchPackage = $input->getArgument('package');
        foreach (preg_split('/' . PHP_EOL . '/', $extensions) as $line) {
            $matches = $this->matchConnectLine($line);
            if (!empty($matches)) {
                if ($matches[1] == $searchPackage) {
                    $found = true;
                    break 1;
                } else {
                    if ($this->isAlternative($matches[1], $searchPackage)) {
                        $alternatives[] = $matches[1];
                    }
                }
            }
        }

        if ($found) {
            $this->installExtension($input, $output, $searchPackage);
        } else {
            $output->writeln('<comment>Could not found package.</comment>');
            if (count($alternatives) > 0) {
                $this->installExtension(
                    $input,
                    $output,
                    $this->askForAlternativePackage($alternatives, $input, $output)
                );
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $package
     */
    protected function installExtension($input, $output, $package)
    {
        $output->writeln($this->callMageScript($input, $output, 'install community ' . $package));
    }

    /**
     * @param string $packageName
     * @param string $searchPackageName
     * @return bool
     */
    protected function isAlternative($packageName, $searchPackageName)
    {
        $lev = levenshtein($packageName, $searchPackageName);

        return $lev <= strlen($searchPackageName) / 3 || false !== strpos($searchPackageName, $packageName);
    }

    /**
     * @param array $alternatives
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function askForAlternativePackage($alternatives, $input, $output)
    {
        foreach ($alternatives as $key => $package) {
            $question[] = '<comment>[' . ($key+1) . ']</comment> ' . $package . "\n";
        }
        $question[] = "<question>Install alternative package? :</question> ";

        $packageNumber = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($alternatives) {
            if (!in_array($typeInput, range(1, count($alternatives)))) {
                throw new \InvalidArgumentException('Invalid type');
            }

            return $typeInput;
        });

        return $alternatives[$packageNumber - 1];
    }
}