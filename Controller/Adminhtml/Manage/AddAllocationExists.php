<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;
use Magento\Framework\Controller\ResultFactory;

class AddAllocationExists extends \Magento\Backend\App\Action
{
    protected $_historyHelperFactory;
    protected $allocationFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ){
        $this->_allocationFactory = $allocationFactory;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        //Action 1= replace 2=add 3=cancel
        if($params["action"] == 3){
            $this->messageManager->addSuccessMessage(__('Process Cancel Product Allocation Exists In The System Success.'));
            return $this->_redirect('productallocation/manage/addaction');
        }else{
            $allocation = $this->_allocationFactory->create();
            $allocation = $allocation->load($params["allocation_id"]);
            $fromQty = $allocation->getQty();
            if($params["action"] == 2){
                $this->messageManager->addSuccessMessage(__('Process Add Product Allocation Exists In The System Success.'));
                $newQuantity = $allocation->getQty() + $params["qty_enter"];
            }else{
                $this->messageManager->addSuccessMessage(__('Process Replace Product Allocation Exists In The System Success.'));
                $newQuantity = $params["qty_enter"];
            }
            $allocation->setQty($newQuantity);
            $allocation->save();
            $historyHelper = $this->_historyHelperFactory->create();
            $historyHelper->writeLogData($params["website_id"],$action='Manage Allocation Edited By '.$params["full_name"],$allocation->getUser(),$allocation->getSku(),$orderId=null,$orderQty=null,$fromQty,$newQuantity);
            return $this->_redirect('productallocation/manage/addaction');
        }
    }
}
