<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class History extends AbstractHelper
{
    protected $_storeManager;
    protected $_historyModelFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\HistoryFactory $historyModelFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_historyModelFactory = $historyModelFactory;
        $this->scopeConfig = $scopeConfig;
    }
    public function writeLogData($websiteId,$action,$email=null,$sku=null,$orderId=null,$orderQty=null,$fromQty=null,$toQty=null)
    {
        try
        {
            $history = $this->_historyModelFactory->create();
            $history->setUser($email)
                ->setSku($sku)
                ->setOrderId($orderId)
                ->setOrderedQty($orderQty)
                ->setFromQty($fromQty)
                ->setToQty($toQty)
                ->setAction($action)
                ->setWebsiteId($websiteId)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->save();
        } catch (Exception $e) {
//            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            return;
        }
    }
}

