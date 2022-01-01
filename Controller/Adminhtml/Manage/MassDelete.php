<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class MassDelete extends \Magento\Backend\App\Action {

    protected $_filter;
    protected $_allocationCollectionFactory;
    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Commercers\ProductAllocation\Model\ResourceModel\Allocation\CollectionFactory $allocationCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_filter            = $filter;
        $this->_allocationCollectionFactory = $allocationCollectionFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $allocationCollection = $this->_filter->getCollection($this->_allocationCollectionFactory->create());
        $allocationDeleted = 0;
        $allocationDeletedError = 0;
        foreach ($allocationCollection as $allocation) {
            try {
//                $this->productRepository->delete($allocation);
                $allocation->delete();
                $allocationDeleted++;
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getLogMessage());
                $allocationDeletedError++;
            }
        }

        if ($allocationDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $allocationDeleted)
            );
        }

        if ($allocationDeletedError) {
            $this->messageManager->addErrorMessage(
                __(
                    'A total of %1 record(s) haven\'t been deleted. Please see server logs for more details.',
                    $allocationDeletedError
                )
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('productallocation/manage/index');
    }
    protected function _isAllowed()
    {
        return true;
    }

}