<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;
use Magento\Framework\Controller\ResultFactory;

class EditAction extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Registry $registry,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory
    ){
        $this->_pageFactory = $pageFactory;
        $this->_coreSession = $coreSession;
        $this->_registry = $registry;
        $this->_allocationFactory = $allocationFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('allocation_id');
        $allocation = $this->_allocationFactory->create();
        $allocation = $allocation->load($id);
        $this->_registry->register('product_allocation', $allocation);
        $this->_getSession()->setProfilerId($id);
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
