<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\ImportData;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var $_resultPageFactory  \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Io\File $fileIo,
        \Magento\Backend\App\Action\Context $context
    ) {

        $this->_resultPageFactory = $resultPageFactory;
        $this->_fileSystem = $fileSystem;
        $this->_fileIo = $fileIo;
        parent::__construct($context);
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Commercers_ProductAllocation::import_data');
    }

    public function execute()
    {
        $path = $this->_fileSystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath('productallocation_importdata');
        $this->_fileIo->mkdir($path, '0777', true);
        if (!is_writable($path)) {
            $this->messageManager->addNotice(__('Please make this directory path writable var/productallocation_importdata'));
        }

        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Commercers_ProductAllocation::productallocation_importdata');
        $resultPage->getConfig()->getTitle()->prepend('Import Product Allocation');

        return $resultPage;
    }
}
