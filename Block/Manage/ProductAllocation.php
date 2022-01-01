<?php

namespace Commercers\ProductAllocation\Block\Manage;
use Commercers\ProductAllocation\Model\Allocation;
use Magento\Catalog\Block\Product\Context;

/**
 * Product View block
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class ProductAllocation extends \Magento\Framework\View\Element\Template
{
    protected $_customerSession;
    protected $registry;
    protected $_allocationFactory;
    protected $_allocationHelper;
    protected $_storeManager;
    protected $_allocationHelperFactory;
    protected $_productRepository;
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Commercers\ProductAllocation\Helper\Allocation $allocationHelper,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_storeManager = $storeManager;
        $this->_allocationHelper = $allocationHelper;
        $this->_allocationFactory = $allocationFactory;
        $this->registry = $registry;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }
    public function getCustomerEmail()
    {
        $customerData = $this->_customerSession->getCustomer();
        return $customerData->getEmail();
    }
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getProductAllocation($email,$sku,$websiteId){
        $productAllocationModel = $this->_allocationFactory->create()->getCollection();
        $productAllocationModel->addFieldToFilter('sku', $sku);
        $productAllocationModel->addFieldToFilter('user', $email);
        $productAllocationModel->addFieldToFilter('website_id', $websiteId);
        return $productAllocationModel->getFirstItem()->getData();
    }

    public function showQtyProductAllocation($sku,$websiteId){
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (intval($helperAllocation->isEnabled($this->getCurrentWebsiteId())) && $this->getRuleProductAllocationBySku($sku,$websiteId)) {
            return true;
        }
        return false;
    }
    public function getCurrentWebsiteId(){
        return $this->_storeManager->getStore()->getWebsiteId();
    }
    public function getRuleProductAllocationBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->_productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }
    public function getStoreIdByWebsiteId(int $websiteId)
    {
        return $this->_storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}