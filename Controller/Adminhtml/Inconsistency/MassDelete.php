<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\Inconsistency;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class MassDelete extends \Magento\Backend\App\Action {

    protected $_filter;
    protected $_inconsistencyCollectionFactory;
    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Commercers\ProductAllocation\Model\ResourceModel\Inconsistency\CollectionFactory $inconsistencyCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_filter            = $filter;
        $this->_inconsistencyCollectionFactory = $inconsistencyCollectionFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $inconsistencyCollection = $this->_filter->getCollection($this->_inconsistencyCollectionFactory->create());
        $inconsistencyDeleted = 0;
        $inconsistencyDeletedError = 0;
        foreach ($inconsistencyCollection as $inconsistency) {
            try {
                $inconsistency->delete();
                $inconsistencyDeleted++;
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getLogMessage());
                $inconsistencyDeletedError++;
            }
        }

        if ($inconsistencyDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $inconsistencyDeleted)
            );
        }

        if ($inconsistencyDeletedError) {
            $this->messageManager->addErrorMessage(
                __(
                    'A total of %1 record(s) haven\'t been deleted. Please see server logs for more details.',
                    $inconsistencyDeletedError
                )
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('productallocation/inconsistency/index');
    }
    protected function _isAllowed()
    {
        return true;
    }

}