<?php

namespace Commercers\ProductAllocation\Controller\Product;

use Commercers\ProductAllocation\Model\Allocation;
use Magento\Framework\App\Action\Action;

class CheckAfterOrder extends Action{

    protected $_pageFactory;
    protected $_productRepository;
    protected $customer;
    protected $_allocationModelFactory;
    protected $_allocationHelperFactory;
    protected $orderRepository;
    protected $_historyHelperFactory;
    public function __construct(
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->customer = $customer;
        $this->_allocationModelFactory = $allocationModelFactory;
        $this->orderRepository = $orderRepository;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
//        $order = $observer->getEvent()->getOrder();
        $orderId = 2;
        $order = $this->orderRepository->get($orderId);
        $storeId = $order->getStoreId();
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (!intval($helperAllocation->isEnabled($storeId))) {
            return ;
        }
        $items = $this->_getProductsQtyFromOrderItems($order->getAllItems());
        foreach ($items as $pId => $item) {
            if (isset($item['allocation']) && $item['allocation']->getId()) {
                $newQuantity = $item['allocation']->getQty() - $item['qty'];
                $item['allocation']->setQty($newQuantity)->save();
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($action='Order Success',$item['customer_email'],$item['sku'],$orderId=$order->getId(),$orderQty=$item['qty'],null,null);;
//                    ->setOrderId($order->getIncrementId())
            }
        }
        return;
    }
    protected function _getProductsQtyFromOrderItems($relatedItems){
        $items = [];
        $bundleItemIds = [];
        foreach ($relatedItems as $item) {
            $productId = $item->getProductId();
            if (!$productId) {
                continue;
            }
            if($item->getProduct()->getTypeId() == 'bundle'){
                $bundleItemIds[] = $item->getId();
                continue;
            }
            $children = $item->getChildren(); // here is a bug in original Mage_CatalogInventory_Model_Observer::_getProductsQty -> getChildrenItems not exist!
//            var_dump($children);exit;
            if ($children) {
                foreach ($children as $childItem) {
                    $childProductId = $childItem->getProductId();
                    if (!$childProductId) {
                        continue;
                    }
                    $childProductlimitallocation = null;
                    if ($childItem->getProduct()) {
                        $customer = $this->customer->load($childItem->getOrder()->getCustomerId());
                        $productlimitallocation = $this->_allocationModelFactory->create();
                        $childProductlimitallocation = $productlimitallocation->getDataAllocation($childItem->getSku(),$customer->getEmail());
                    }
                    $items[$childProductId] = array (
                        'allocation' => $childProductlimitallocation,
                        'qty' => $childItem->getQtyOrdered(),
                        'customer_email' => $customer->getEmail(),
                        'sku' => $children->getSku()
                    );
//                    $items[$childProductId]['customer_email'] = $customer->getEmail();
//                    $items[$childProductId]['sku'] = $childItem->getSku();
//                    if (isset($items[$childProductId])) {
//                        $items[$childProductId]['qty'] += $childItem->getQtyOrdered();
//                    } else {
//                        $items[$childProductId] = array (
//                            'allocation' => $childProductlimitallocation,
//                            'qty' => $childItem->getQtyOrdered()
//                        );
//                    }
                }
            }else{
                // if item is child skip this item, because it is calculated in children loop
                if ($item->getParentItemId()) {
                    if(!in_array($item->getParentItemId(), $bundleItemIds)){
                        continue;
                    }
                }
                $productlimitallocation = null;
//                $storeId = $item->getOrder()->getStoreId();
//                $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
                if ($item->getProduct()) {
                    $customer = $this->customer->load($item->getOrder()->getCustomerId());
                    $productlimitallocation = $this->_allocationModelFactory->create();
                    $productlimitallocation = $productlimitallocation->getDataAllocation($item->getSku(),$customer->getEmail());
                }
                $items[$productId] = array(
                    'allocation' => $productlimitallocation,
                    'qty' => $item->getQtyOrdered(),
                    'customer_email' => $customer->getEmail(),
                    'sku' => $item->getSku()
                );
//                $items[$productId]['customer_email'] = $customer->getEmail();
//                $items[$productId]['sku'] = $item->getSku();
//                if(isset($items[$productId]['qty'])){
//                    $items[$productId]['qty'] += $items[$productId]['qty'];
//                }else{
//                    $items[$productId]['qty'] = 0;
//                }
//                if (isset($items[$productId])) { // here is a bug in original Mage_CatalogInventory_Model_Observer::_getProductsQty -> tests for $item not $items
//                    $items[$productId]['qty'] += $item->getQtyOrdered();
//                }else{
//                    $items[$productId] = array(
//                        'allocation' => $productlimitallocation,
//                        'qty' => $item->getQtyOrdered()
//                    );
//                }
            }
        }
        return $items;
    }
}
