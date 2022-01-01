<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Inconsistency;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;

class Save extends Action
{
    /**
     * @var $_resultPageFactory  \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    protected $_allocationFactory;
    protected $_historyHelperFactory;
    protected $_inconsistencyFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_allocationFactory = $allocationFactory;
        $this->_resultPageFactory = $resultPageFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Commercers_ProductAllocation::inconsistency');
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $inconsistencyId = $data["inconsistency_id"];
        $dataNewQty = $data["new_qty"];
        $newQtyInconsistency = 0;
        $countIndividuals = 0;
        $groupQtyNotAssigned = 0;
        foreach($dataNewQty as $key => $value){
            $countIndividuals++;
            $allocationModel = $this->_allocationFactory->create()->load($key);
            if($allocationModel->getGroupQtyNotAssigned() == 1){
                $groupQtyNotAssigned = $allocationModel->getQty();
                continue;
            }
            if($value){
                $newQtyInconsistency += $value;
                $fromQty = $allocationModel->getQty();
                $allocationModel->setQty($value);
                $allocationModel->save();
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($allocationModel->getWebsiteId(),$action='manage allocation edited',$allocationModel->getUser(),$allocationModel->getSku(),$orderId=null,$orderQty=null,$fromQty,$value);
            }else{
                $newQtyInconsistency += $allocationModel->getQty();
            }
        }
        $newQtyInconsistency += $groupQtyNotAssigned;
        $inconsistencyModel = $this->_inconsistencyFactory->create()->load($inconsistencyId);
        $inconsistencyModel->setAllocation($newQtyInconsistency);
        $inconsistencyModel->setDifference($inconsistencyModel->getQuantity()-$newQtyInconsistency);
        $inconsistencyModel->setInformation('Individuals // '.$countIndividuals.' allocation(s) // sum of allocations: ('.$newQtyInconsistency.')');
        $inconsistencyModel->save();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getParam('back')) {
            $this->messageManager->addSuccessMessage(__('Thanks For Requesting. Current allocation difference is '.$inconsistencyModel->getDifference()));
            return $resultRedirect->setPath('productallocation/inconsistency/edit/inconsistency_id/'.$inconsistencyId);
        }
        $this->messageManager->addSuccessMessage(__('Thanks For Requesting.'));
        return $resultRedirect->setPath('*/*/');
//        return $resultRedirect->setPath('productallocation/inconsistency/');
    }
}
