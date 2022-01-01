<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\Manage;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;

use Commercers\ProductAllocation\Model\ResourceModel\Allocation\CollectionFactory;
class Form extends Generic implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $conditions;

    protected $resource;

    protected $_backendUrl;

    protected $_storeManager;

    /**
     * @var Registry
     */
    public $coreRegistry;

    /**
     * Supervisor Collection
     *
     * @var \Commercers\ProductAllocation\Model\ResourceModel\Allocation\CollectionFactory
     */
    protected $_suppervisorCollectionFactory;

    /**
     * Rule Model
     *
     * @var \Commercers\ProductAllocation\Model\AllocationFactory
     */
    protected $_allocationFactory;

    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        CollectionFactory $suppervisorCollectionFactory,
        Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\Store\Model\StoreRepository $StoreRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_allocationFactory = $allocationFactory;
        $this->_suppervisorCollectionFactory = $suppervisorCollectionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        $this->_backendUrl = $backendUrl;
        $this->resource = $resource;
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
        $this->scopeConfig = $scopeConfig;
        $this->StoreRepository = $StoreRepository;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Supervisors');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Supervisors');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Generic
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Commercers_ProductAllocation::manage/information.phtml');
        return $this;
    }

    public function getBaseBackendUrl($url)
    {
        return $this->_backendUrl->getUrl($url);
    }

    public function getBaseMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getOrderApprovalRule()
    {
        return $this->coreRegistry->registry('product_allocation');
    }

    public function  getSuppervisorCollection($allocationId)
    {
        $collection = $this->_suppervisorCollectionFactory->create();
        $collection->addFieldToFilter('allocation_id', $allocationId);
        $result = $collection->getData();
        return $result;
    }
    public function getProductAllocation($allocationId)
    {
        $allocationModel = $this->_allocationFactory->create();
        $result = $allocationModel->load($allocationId);
        return $result->getData();
    }
//    public function getApprovalRule($ruleId)
//    {
//        $ruleModel = $this->_allocationFactory->create();
//        $result = $ruleModel->load($ruleId);
//        return $result->getData();
//    }
}
