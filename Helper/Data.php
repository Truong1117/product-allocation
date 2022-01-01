<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'productallocation/general/enabled';
    const XML_PATH_MESSAGE_ERROR = 'productallocation/general/message_error';
    const XML_PATH_IS_CREDITBACK_CONFIG_ENABLED = 'productallocation/settings/creditbackconfigure_enabled';
    const XML_PATH_CREDITBACK_STATUSES = 'productallocation/settings/creditbackstatus';
    protected $_storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_storeManager = $storeManager;
            $this->scopeConfig = $scopeConfig;
    }
    public function getConfigValue($path,$storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    public function getMessageError(){
        return $this->getConfigValue(self::XML_PATH_MESSAGE_ERROR);
    }
    public function getCurrentWebsiteId(){
        return $this->_storeManager->getStore()->getWebsiteId();
    }
}

