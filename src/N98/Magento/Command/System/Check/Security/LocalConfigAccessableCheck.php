<?php

namespace N98\Magento\Command\System\Check\Security;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;

class LocalConfigAccessableCheck implements SimpleCheck
{
    /**
     * @var int
     */
    protected $_verificationTimeOut = 30;

    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results)
    {
        $result = $results->createResult();
        $filePath = 'app/etc/local.xml';
        $defaultUnsecureBaseURL = (string) \Mage::getConfig()->getNode(
            'default/' . \Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL
        );

        $http = new \Varien_Http_Adapter_Curl();
        $http->setConfig(array('timeout' => $this->_verificationTimeOut));
        $http->write(\Zend_Http_Client::POST, $defaultUnsecureBaseURL . $filePath);
        $responseBody = $http->read();
        $responseCode = \Zend_Http_Response::extractCode($responseBody);
        $http->close();

        if ($responseCode === 200) {
            $result->setStatus(Result::STATUS_ERROR);
            $result->setMessage("<error>$filePath can be accessed from outside!</error>");
        } else {
            $result->setStatus(Result::STATUS_OK);
            $result->setMessage("<info><comment>$filePath</comment> cannot be accessed from outside.</info>");
        }
    }
}
