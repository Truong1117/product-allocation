<?php

namespace Commercers\ProductAllocation\Controller\Manage;
use Commercers\ProductAllocation\Helper\Customer;
use Magento\Framework\App\Action\Context;
class ChildProductConfigurable extends \Magento\Framework\App\Action\Action
{
    protected $_allocationHelperFactory;
    protected $_dataHelperFactory;
    protected $customerFactory;
    protected $productHelperFactory;
    public function __construct(
        \Commercers\ProductAllocation\Helper\ProductFactory $productHelperFactory,
        \Commercers\ProductAllocation\Helper\CustomerFactory $customerFactory,
        \Commercers\ProductAllocation\Helper\DataFactory $dataHelperFactory,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        Context $context
    ) {
        $this->productHelperFactory = $productHelperFactory;
        $this->customerFactory = $customerFactory;
        $this->_dataHelperFactory = $dataHelperFactory;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $result = [];
        $productId = $this->getRequest()->getParam('product_id');
        $isHasQtyAllocation = false;
        $qtyAllocation = 0;
        $allocationId = '';
        $qtyMinSale = 0;
        if($productId){
            $dataHelper = $this->_dataHelperFactory->create();
            $allocationHelper = $this->_allocationHelperFactory->create();
            $emailCurrent = $this->customerFactory->create()->getCustomerEmail();
            $isRuleProductAllocation = $allocationHelper->getRuleProductAllocationById($productId,$dataHelper->getCurrentWebsiteId());
            if(intval($isRuleProductAllocation) === 1){
                $productHelper = $this->productHelperFactory->create();
                $product = $productHelper->loadProductById($productId);
                $stockRegistry = $productHelper->getStockRegistry();
                $productStock = $stockRegistry->getStockItem($productId) ? $stockRegistry->getStockItem($productId) : '';
                $productAllocation = $allocationHelper->getProductAllocation($emailCurrent,$product->getSku(),$dataHelper->getCurrentWebsiteId());
                if($productAllocation){
                    $qtyMinSale = $productStock->getMinSaleQty();
                    $qtyAllocation = $productAllocation["qty"];
                    $allocationId = $productAllocation["allocation_id"];
                }
                $isHasQtyAllocation = true;
            }
        }
        $result['qty_min_sale'] = $qtyMinSale;
        $result['allocation_id'] = $allocationId;
        $result['qty_allocation'] = $qtyAllocation;
        $result['is_has_qty_allocation'] = $isHasQtyAllocation;
        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }
}