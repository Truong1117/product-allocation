<?php

namespace Commercers\ProductAllocation\Controller\Manage;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $customerSession;
    public function __construct(
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Context $context
    ) {
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (!intval($helperAllocation->isEnabled())) {
            return $this->_redirect('/');
        }
        $emailCustomerCurrent = $this->getCustomer()->getEmail();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Quota deposited for').' '.$emailCustomerCurrent. ' '.date("d.m.Y H:i:s",time()));
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

}