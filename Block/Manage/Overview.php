<?php
namespace Commercers\ProductAllocation\Block\Manage;
use Magento\Framework\View\Element\Template;
class OverView extends Template
{
    protected $_allocationHelperFactory;
    protected $_allocationModelFactory;
    protected $customerSession;
    protected $registry;
    protected $productRepository;
    protected $storeManager;
    protected $imageHelper;
    protected $_scopeConfig;
    protected $configurableModel;
    protected $_productHelperFactory;
    protected $configurableProductViewType;
    public function __construct(
        \Commercers\ProductAllocation\Helper\ProductFactory $productHelperFactory,
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $configurableProductViewType,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableModel,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $resourceConfigurable,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        Template\Context $context
    ) {
        $this->_productHelperFactory = $productHelperFactory;
        $this->configurableProductViewType = $configurableProductViewType;
        $this->configurableModel = $configurableModel;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->resourceConfigurable = $resourceConfigurable;
        $this->stockRegistry = $stockRegistry;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        $this->_allocationModelFactory = $allocationModelFactory;
        parent::__construct($context);
    }

    public function getProductAllocationByEmail(){
        $websiteId = $this->getCurrentWebsiteId();
        $allocationCollection = $this->_allocationModelFactory->create()->getCollection();
        $allocationCollection->addFieldToFilter('user', $this->getCurrentEmail());
        $allocationCollection->addFieldToFilter('qty',['gt' => 0]);
        $allocationCollection->addFieldToFilter('website_id', $websiteId);
        return $allocationCollection->getData();
    }

    public function getCurrentEmail(){
        if($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getEmail();
        }
        return null;
    }

    public function loadProductBySku($sku)
    {
        return $this->productRepository->get($sku);
    }
    protected function getProductMinSaleQty($sku){
        $product = $this->loadProductBySku($sku);
        $itemStockModel = $this->itemStockFactory->create();
        $productQuantity = $itemStockModel->loadByProduct($product->getId());
        return $productQuantity->getMinSaleQty();
    }
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
    public function loadImageThumbnail($sku)
    {
        $productHelper = $this->_productHelperFactory->create();
        return $productHelper->loadImageThumbnail($sku);
//        $product = $this->loadProductBySku($sku);
//        return $this->imageHelper->init($product, 'small_image')
//            ->setImageFile($product->getSmallImage()) // image,small_image,thumbnail
//            ->resize(80,80)
//            ->getUrl();
////        return $this->imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getFile())->resize(80,80)->getUrl();
    }
    public function getStockRegistry()
    {
        return $this->stockRegistry;
    }
    public function getCurrentWebsiteId(){
        return $this->storeManager->getStore()->getWebsiteId();
    }

    public function getParentProductBySkuChild($childSku){
        $productHelper = $this->_productHelperFactory->create();
        return $productHelper->getParentProductBySkuChild($childSku);
    }

    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    public function isProductEnable($sku){
        $websiteId = $this->getCurrentWebsiteId();
        $allocationHelper = $this->_allocationHelperFactory->create();
        return $allocationHelper->getIsProductEnable($sku,$websiteId);
    }
    public function isRuleProductAllocationBySku($sku){
        $websiteId = $this->getCurrentWebsiteId();
        $allocationHelper = $this->_allocationHelperFactory->create();
        return $allocationHelper->getRuleProductAllocationBySku($sku,$websiteId);
    }
    public function getAttributeOptionsProductConfig(int $id)
    {
        $productHelper = $this->_productHelperFactory->create();
        return $productHelper->getAttributeOptionsProductConfig($id);
    }
    public function getPlaceholderImage(){
//        return $this->_scopeConfig->getValue('catalog/placeholder/image_placeholder'); // Base Image
        return $this->_scopeConfig->getValue('catalog/placeholder/small_image_placeholder'); // Small Image
//        return $this->_scopeConfig->getValue('catalog/placeholder/swatch_image_placeholder'); // Swatch Image
//        return $this->_scopeConfig->getValue('catalog/placeholder/thumbnail_placeholder'); // Thumbnail Image
    }
}