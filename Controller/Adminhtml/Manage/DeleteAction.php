<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;

class DeleteAction extends \Magento\Backend\App\Action
{
    protected $_allocationFactory;
    protected $_historyHelperFactory;
    protected $authSession;
    protected $productRepository;
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory
    ){
        $this->productRepository = $productRepository;
        $this->authSession = $authSession;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_pageFactory = $pageFactory;
        $this->_allocationFactory = $allocationFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $currentUser = $this->authSession->getUser();
        $fullNameUser = $currentUser->getLastname().' '.$currentUser->getFirstname();
        $allocationId = $this->getRequest()->getParam('allocation_id');
        $collection = $this->_allocationFactory->create()->load($allocationId);
        $historyHelper = $this->_historyHelperFactory->create();
        $historyHelper->writeLogData($collection->getWebsiteId(),$action='Manage Allocation Deleted By '.$fullNameUser,$collection->getUser(),$collection->getSku(),$orderId=null,$orderQty=null,$collection->getQty(),0);
        $collection->delete();
        $this->messageManager->addSuccessMessage(__('Product Allocation Is Deleted'));
        $this->_redirect('productallocation/manage/index');
    }
}
