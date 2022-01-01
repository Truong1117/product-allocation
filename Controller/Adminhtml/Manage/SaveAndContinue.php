<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;

class SaveAndContinue extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $allocationFactory;

    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_allocationFactory = $allocationFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        echo 123;exit;
        $params = $this->getRequest()->getPostValue();
        $dataAllocation = $params["allocation"];
        $allocation = $this->_allocationFactory->create();

        if (isset($dataAllocation['allocation_id'])) {
            $allocation = $allocation->load($dataAllocation['allocation_id']);
        }
        try {
            $allocation->addData([
                'sku' => $dataAllocation['sku'],
                'user' => $dataAllocation['email'],
                'qty' => $dataAllocation['qty'],
                'user_group' => $dataAllocation['user_group'],
                'allocation_type' => $dataAllocation['allocation_type']
            ])->save();
            $this->_redirect('*/*/editaction', ['allocation_id' => $allocation->getAllocationId(), '_current' => true]);
        } catch (Exception $e) {
            //echo $e->getMessage();exit;
            $this->_redirect('*/*/listing');
        }
    }
}
