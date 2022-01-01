<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Inconsistency;

use Commercers\ProductAllocation\Model\Inconsistency;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var $_resultPageFactory  \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    protected $_inconsistencyFactory;
    protected $_productRepository;
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Io\File $fileIo,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_registry = $registry;
        $this->_productRepository = $productRepository;
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_fileSystem = $fileSystem;
        $this->_fileIo = $fileIo;
        parent::__construct($context);
    }

    public function execute()
    {
//        4711 Testproduct = Sku + Product name
//Current Allocation Difference: 674 = Message + Qty Difference
        $inconsistencyId = $this->getRequest()->getParam('inconsistency_id');
        $this->_registry->register('current_inconsistency_id', $inconsistencyId);
        $inconsistencyModel = $this->_inconsistencyFactory->create()->load($inconsistencyId);
        $productModel = $this->_productRepository->get($inconsistencyModel->getSku());
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Commercers_ProductAllocation::salesforce_contact_import');
        $resultPage->getConfig()->getTitle()->prepend($inconsistencyModel->getSku(). ' '.$productModel->getName());
        return $resultPage;
    }
}
