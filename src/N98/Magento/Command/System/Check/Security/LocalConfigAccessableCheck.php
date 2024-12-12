<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Security;

use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use Varien_Http_Adapter_Curl;
use Zend_Http_Client;
use Zend_Http_Response;

/**
 * Class LocalConfigAccessableCheck
 *
 * @package N98\Magento\Command\System\Check\Security
 */
class LocalConfigAccessableCheck implements SimpleCheck
{
    protected int $_verificationTimeOut = 30;

    public function check(ResultCollection $resultCollection): void
    {
        $result = $resultCollection->createResult();
        $filePath = 'app/etc/local.xml';
        $defaultUnsecureBaseURL = (string) Mage::getConfig()->getNode(
            'default/' . Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL,
        );

        $varienHttpAdapterCurl = new Varien_Http_Adapter_Curl();
        $varienHttpAdapterCurl->setConfig(['timeout' => $this->_verificationTimeOut]);
        $varienHttpAdapterCurl->write(Zend_Http_Client::POST, $defaultUnsecureBaseURL . $filePath);

        $responseBody = (string) $varienHttpAdapterCurl->read();
        $responseCode = Zend_Http_Response::extractCode($responseBody);
        $varienHttpAdapterCurl->close();

        if ($responseCode === 200) {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage(sprintf('<error>%s can be accessed from outside!</error>', $filePath));
        } else {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage(sprintf('<info><comment>%s</comment> cannot be accessed from outside.</info>', $filePath));
        }
    }
}
