<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:check')
            ->setAliases(array('system:check'))
            ->addDeprecatedAlias('system:check', 'Please use sys:check')
            ->setDescription('Checks Magento System');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento($output, true)) {

            if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
                $output->writeln("<error>WARNING: Magento 2 requirements are not yet defined. Until then Magento 1 requirements are checked.</error>");
            }

            $this->checkFilesystem($input, $output);
            $this->checkPhp($input, $output);
            $this->checkSecurity($input, $output);
            $this->checkMysql($input, $output);
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function checkFilesystem($input, $output)
    {
        $this->writeSection($output, 'Check: Filesystem');

        /**
         * Check folders
         */
        $folders = array(
            array('media', 'Used for images and other media files.'),
            array('var', 'Used for caching, reports, etc.'),
            array('var/cache', 'Used for caching'),
            array('var/session', 'Used as file based sesssion save'),
        );

        foreach ($folders as $folder) {
            if (file_exists($this->_magentoRootFolder . DIRECTORY_SEPARATOR . $folder[0])) {
                $output->writeln("<info>Folder <comment>" . $folder[0] . "</comment> found.</info>");
                if (!is_writeable($this->_magentoRootFolder . DIRECTORY_SEPARATOR . $folder[0])) {
                    $output->writeln("<error>Folder " . $folder[0] . " is not writeable!</error><comment> Usage: " . $folder[1] . "</comment>");
                }
            } else {
                $output->writeln("<error>Folder " . $folder[0] . " not found!</error><comment> Usage: " . $folder[1] . "</comment>");
            }
        }

        /**
         * Check files
         */
        $files = array(
            array('app/etc/local.xml', 'Magento local configuration.'),
            array('index.php.sample', 'Used to generate staging websites in Magento enterprise edition'),
        );

        foreach ($files as $file) {
            if (file_exists($this->_magentoRootFolder . DIRECTORY_SEPARATOR . $file[0])) {
                $output->writeln("<info>File <comment>" . $file[0] . "</comment> found.</info>");
            } else {
                $output->writeln("<error>File " . $file[0] . " not found!</error><comment> Usage: " . $file[1] . "</comment>");
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function checkPhp($input, $output)
    {
        $this->writeSection($output, 'Check: PHP');

        $requiredExtensions = array(
            'simplexml',
            'mcrypt',
            'hash',
            'gd',
            'dom',
            'iconv',
            'curl',
            'soap',
            'pdo',
            'pdo_mysql',
        );

        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $output->writeln("<info>Required PHP Module <comment>$ext</comment> found.</info>");
            } else {
                $output->writeln("<error>Required PHP Module $ext not found!</error>");
            }
        }

        /**
         * Check Bytecode Cache
         */
        $bytecopdeCacheExtensions = array(
            'apc',
            'eaccelerator',
            'xcache',
            'Zend Optimizer'
        );
        $bytecodeCacheExtensionLoaded = false;
        $bytecodeCacheExtension = null;
        foreach ($bytecopdeCacheExtensions as $ext) {
            if (extension_loaded($ext)) {
                $bytecodeCacheExtension = $ext;
                $bytecodeCacheExtensionLoaded = true;
                break;
            }
        }
        if ($bytecodeCacheExtensionLoaded) {
            $output->writeln("<info>Bytecode Cache <comment>$bytecodeCacheExtension</comment> found.</info>");
        } else {
            $output->writeln("<error>No Bytecode-Cache found!</error> <comment>It's recommended to install anyone of " . implode(', ', $bytecopdeCacheExtensions) . ".</comment>");
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function checkSecurity($input, $output)
    {
        $this->writeSection($output, 'Check: Security');

        $filePath = 'app/etc/local.xml';
        $defaultUnsecureBaseURL = (string) \Mage::getConfig()->getNode('default/' . \Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL);

        $http = new \Varien_Http_Adapter_Curl();
        $http->setConfig(array('timeout' => $this->_verificationTimeOut));
        $http->write(\Zend_Http_Client::POST, $defaultUnsecureBaseURL . $filePath);
        $responseBody = $http->read();
        $responseCode = \Zend_Http_Response::extractCode($responseBody);
        $http->close();

        if ($responseCode === 200) {
            $output->writeln("<error>$filePath can be accessed from outside!</error>");
        } else {
            $output->writeln("<info><comment>$filePath</comment> cannot be accessed from outside.</info>");
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function checkMysql($input, $output)
    {
        $this->writeSection($output, 'Check: MySQL');

        $dbAdapter = $this->_getModel('core/resource', 'Mage_Core_Model_Resource')->getConnection('core_write');

        /**
         * Check Version
         */
        $mysqlVersion = $dbAdapter->fetchOne('SELECT VERSION()');
        if (version_compare($mysqlVersion, '4.1.20', '>=')) {
            $output->writeln("<info>MySQL Version <comment>$mysqlVersion</comment> found.</info>");
        } else {
            $output->writeln("<error>MySQL Version $mysqlVersion found. Upgrade your MySQL Version.</error>");
        }

            /**
         * Check Engines
         */
        $engines = $dbAdapter->fetchAll('SHOW ENGINES');
        $innodbFound = false;
        foreach ($engines as $engine) {
            if (strtolower($engine['Engine']) == 'innodb') {
                $innodbFound = true;
                break;
            }
        }

        if ($innodbFound) {
            $output->writeln("<info>Required MySQL Storage Engine <comment>InnoDB</comment> found.</info>");
        } else {
            $output->writeln("<error>Required MySQL Storage Engine \"InnoDB\" not found!</error>");
        }
    }
}