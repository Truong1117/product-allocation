<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\Inconsistency;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
class Allocation extends Action{

    protected $_resultPageFactory;
    protected $_allocationFactory;
    protected $_inconsistencyFactory;
    protected $_registry;

    public function __construct(

        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Registry $registry,
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Context $context
    ){
        parent::__construct($context);

        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->_registry = $registry;
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->_allocationFactory = $allocationFactory;
        $this->_resultPageFactory = $resultPageFactory;
    }

//    protected function _isAllowed()
//    {
//        return $this->_authorization->isAllowed('Commercers_OrderApproval::rule');
//    }

    public function execute(){
        $productallocations = $this->initProductAllocations();
        $this->_registry->register('product_allocation_inconsistency', $productallocations);
//        return $this->_resultPageFactory->create();
        return $this->resultLayoutFactory->create();
    }
    public function initProductAllocations(){
        $inconsistencyId = (int)$this->getRequest()->getParam('inconsistency_id');
        $inconsistencyModel = $this->_inconsistencyFactory->create()->load($inconsistencyId);
        $productAllocations = $this->_allocationFactory->create()->getCollection();
        $productAllocations->addFieldToFilter('sku', $inconsistencyModel->getSku());
        return $productAllocations;
    }

}
