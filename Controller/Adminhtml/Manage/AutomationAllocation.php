<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;

use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;

class AutomationAllocation extends Action
{
    /**
     * @var $_resultPageFactory  \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }
    public function execute()
    {
        echo 123;exit;
    }
}
