<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Scope extends AbstractHelper
{
    protected $_systemStore;
    protected $_storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Store\Model\System\Store $systemStore,
        Context $context
    ) {
        $this->_storeManager = $storeManagerInterface;
        $this->_systemStore = $systemStore;
        parent::__construct($context);
    }
    const XML_PATH_SCOPE_FIELD = 'productlimitallocation/general/scope';
    const XML_PATH_GROUP_ALLOCATION_ATTRIBUTE = 'productlimitallocation/general/allocation_group_attribute';


//    public function getScopeField(){
//        return Mage::getStoreConfig(self::XML_PATH_SCOPE_FIELD);
//    }
//
    public function getGroupCustomerAttributeCode(){
        return $this->getConfigValue(self::XML_PATH_GROUP_ALLOCATION_ATTRIBUTE,$this->_storeManager->getStore()->getId());
    }

    public function getScopeFormValues(){
        return $this->_systemStore->getWebsiteValuesForForm();
    }

    public function getScopeOptionHash(){
        return $this->_systemStore->getWebsiteOptionHash();
    }
    protected function getConfigValue($path,$storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}

