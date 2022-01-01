<?php

namespace Commercers\ProductAllocation\Controller\Manage;
use Magento\Framework\App\Action\Context;
class Allocation extends \Magento\Framework\App\Action\Action
{
    protected $_allocationModelFactory;
    protected $productRepository;
    protected $imageHelper;
    protected $storeManager;
    public function __construct(
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
        $this->_allocationModelFactory = $allocationModelFactory;
    }
    public function execute()
    {
        $result = [];
        $result['display'] = false;
        $websiteId = $this->getCurrentWebsiteId();
        $customer = $this->customerSessionFactory->create();
        $emailCustomer = $customer->getCustomer()->getEmail();
        $params = $this->getRequest()->getParams();
        $productId = $params["product_id"];
        $product = $this->loadProductById($productId);
        $productAllocationModel = $this->_allocationModelFactory->create();
        $productAllocationCollection = $productAllocationModel->getDataAllocation(trim($product->getSku()),trim($emailCustomer),trim($websiteId));
        $productAllocation = $productAllocationCollection->getData();
        $result['qty_product_allocation'] = 0;
        if ($this->showQtyProductAllocation($product->getSku(), $websiteId)) {
            $result['display'] = true;
            if($productAllocation){
                $result['qty_product_allocation'] = $productAllocation["qty"];
            }
        }
        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }
    public function showQtyProductAllocation($sku,$websiteId){
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (intval($helperAllocation->isEnabled($this->getCurrentWebsiteId())) && $this->getRuleProductAllocationBySku($sku,$websiteId)) {
            return true;
        }
        return false;
    }
    public function getRuleProductAllocationBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }
    public function getStoreIdByWebsiteId(int $websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }
    public function getCurrentWebsiteId(){
        return $this->storeManager->getStore()->getWebsiteId();
    }
    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }
}