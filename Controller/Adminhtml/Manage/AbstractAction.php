<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;
use Commercers\ProductAllocation\Model\Allocation as Allocation;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
/**
 * Create CMS page action.
 */
abstract class AbstractAction extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    protected $_allocationFactory;
    protected $_historyHelperFactory;
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory
    ){
        $this->_registry = $registry;
        $this->_coreSession = $coreSession;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_pageFactory = $pageFactory;
        $this->_allocationFactory = $allocationFactory;
        return parent::__construct($context);
    }

}
