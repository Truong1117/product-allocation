<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
class Product extends AbstractHelper
{
    protected $productRepository;
    protected $resourceConfigurable;
    protected $helper;
    /**
     * @var ConfigurableAttributeData
     */
    protected $configurableAttributeData;
    protected $imageHelper;
    protected $stockRegistry;
    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $resourceConfigurable,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->imageHelper = $imageHelper;
        $this->configurableAttributeData = $configurableAttributeData;
        $this->helper = $helper;
        $this->resourceConfigurable = $resourceConfigurable;
        $this->productRepository = $productRepository;
    }
    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    public function loadProductBySku($sku)
    {
        return $this->productRepository->get($sku);
    }
    public function getParentProductBySkuChild($childSku){
        $productChild = $this->loadProductBySku($childSku);
        $childId = $productChild->getEntityId();
        $parentIds = $this->resourceConfigurable->getParentIdsByChild($childId);
        $parentProducts = [];
        foreach ($parentIds as $parentId){
            $parentProduct = $this->loadProductById($parentId);
            if($parentProduct->getData()){
                $parentProducts[$parentProduct->getEntityId()]['entity_id'] = $parentProduct->getEntityId();
                $parentProducts[$parentProduct->getEntityId()]['type_id'] = $parentProduct->getTypeId();
                $parentProducts[$parentProduct->getEntityId()]['product_sku'] = $parentProduct->getSku();
                $parentProducts[$parentProduct->getEntityId()]['product_name'] = $parentProduct->getName();
                $parentProducts[$parentProduct->getEntityId()]['url_key'] = $parentProduct->getUrlKey();
            }
        }
        return $parentProducts;
    }

    public function getAttributeOptionsProductConfig(int $id)
    {
        $parentProduct = $this->loadProductById($id);
        $options = $this->helper->getOptions($parentProduct, $this->getAllowProducts($parentProduct));
        $attributesData = $this->configurableAttributeData->getAttributesData($parentProduct, $options);
        return $attributesData["attributes"];
    }
    public function getAllowProducts($parentProduct)
    {
        $products = [];
        $allProducts = $parentProduct->getTypeInstance()->getUsedProducts($parentProduct, null);
        /** @var $product \Magento\Catalog\Model\Product */
        foreach ($allProducts as $product) {
            if ((int) $product->getStatus() === Status::STATUS_ENABLED) {
                $products[] = $product;
            }
        }
        return $products;
    }
    public function loadImageThumbnail($sku)
    {
        $product = $this->loadProductBySku($sku);
        return $this->imageHelper->init($product, 'small_image')
            ->setImageFile($product->getSmallImage()) // image,small_image,thumbnail
            ->resize(80,80)
            ->getUrl();
//        return $this->imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getFile())->resize(80,80)->getUrl();
    }
    public function getStockRegistry()
    {
        return $this->stockRegistry;
    }

}

