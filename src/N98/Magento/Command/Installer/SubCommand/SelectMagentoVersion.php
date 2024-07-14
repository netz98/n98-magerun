<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class SelectMagentoVersion
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class SelectMagentoVersion extends AbstractSubCommand
{
    /**
     * Check PHP environment against minimal required settings modules
     *
     * @return void
     */
    public function execute()
    {
        if ($this->input->getOption('noDownload')) {
            return;
        }

        if (
            $this->input->getOption('magentoVersion') === null
            && $this->input->getOption('magentoVersionByName') === null
        ) {
            $choices = [];
            foreach ($this->commandConfig['magento-packages'] as $key => $package) {
                $choices[$key + 1] = '<comment>' . $package['name'] . '</comment> ';
            }

            $question = new ChoiceQuestion('<question>Choose a magento version:</question>', $choices);
            $question->setValidator(function ($typeInput) {
                if (!in_array(
                    $typeInput - 1,
                    range(0, count($this->commandConfig['magento-packages']) - 1),
                    true
                )) {
                    throw new \InvalidArgumentException('Invalid type');
                }

                return $typeInput;
            });

            $type = $this->getCommand()->getHelper('question')->ask(
                $this->input,
                $this->output,
                $question
            );
        } else {
            $type = null;

            if ($this->input->getOption('magentoVersion')) {
                $type = $this->input->getOption('magentoVersion');
            } elseif ($this->input->getOption('magentoVersionByName')) {
                foreach ($this->commandConfig['magento-packages'] as $key => $package) {
                    if ($package['name'] === $this->input->getOption('magentoVersionByName')) {
                        $type = $key + 1;
                        break;
                    }
                }
            }

            if ($type == null) {
                throw new \InvalidArgumentException('Unable to locate Magento version');
            }
        }

        $this->config['magentoVersionData'] = $this->commandConfig['magento-packages'][$type - 1];
    }
}
