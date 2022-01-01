<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Marketing extends AbstractHelper

{
    protected $_storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        Context $context
    ) {
        $this->_storeManager = $storeManagerInterface;
        parent::__construct($context);
    }
    public function getMarketingGroups(){
        $marketingCustomerGroups = $this->getConfigValue('productallocation/allocation_transfer/marketing_customer_group',$this->_storeManager->getStore()->getId());
        $bfMarketingGroups = array_map('intval', explode(",", $marketingCustomerGroups));
//        $bfMarketingGroups = explode(",", $marketingCustomerGroups);
        return $bfMarketingGroups;
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

