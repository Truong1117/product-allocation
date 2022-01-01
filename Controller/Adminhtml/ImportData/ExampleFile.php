<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\ImportData;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;

class ExampleFile extends \Magento\Backend\App\Action
{
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Commercers_ProductAllocation::import_data');
    }

    public function execute()
    {
        $filepath = 'productallocation/export/extra_data.csv';
        $downloadedFileName = 'extra_data.csv';
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 0;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::VAR_DIR);
    }
}
