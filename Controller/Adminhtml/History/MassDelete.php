<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\History;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class MassDelete extends \Magento\Backend\App\Action {

    protected $_filter;
    protected $_historyCollectionFactory;
    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Commercers\ProductAllocation\Model\ResourceModel\History\CollectionFactory $historyCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_filter            = $filter;
        $this->_historyCollectionFactory = $historyCollectionFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $historyCollection = $this->_filter->getCollection($this->_historyCollectionFactory->create());
        $historyDeleted = 0;
        $historyDeletedError = 0;
        foreach ($historyCollection as $history) {
            try {
                $history->delete();
                $historyDeleted++;
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getLogMessage());
                $historyDeletedError++;
            }
        }

        if ($historyDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $historyDeleted)
            );
        }

        if ($historyDeletedError) {
            $this->messageManager->addErrorMessage(
                __(
                    'A total of %1 record(s) haven\'t been deleted. Please see server logs for more details.',
                    $historyDeletedError
                )
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('productallocation/history/index');
    }
    protected function _isAllowed()
    {
        return true;
    }

}