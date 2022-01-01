<?php

namespace Commercers\ProductAllocation\Controller\Manage;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
class Filter extends \Magento\Framework\App\Action\Action
{
    protected $_productHelperFactory;
    protected $_allocationHelperFactory;
    protected $_allocationModelFactory;
    protected $imageHelper;
    protected $storeManager;
    protected $helper;
    /**
     * @var ConfigurableAttributeData
     */
    protected $configurableAttributeData;
    public function __construct(
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Commercers\ProductAllocation\Helper\ProductFactory $productHelperFactory,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        Context $context
    ) {
        $this->configurableAttributeData = $configurableAttributeData;
        $this->helper = $helper;
        $this->_productHelperFactory = $productHelperFactory;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->imageHelper = $imageHelper;
        $this->_allocationModelFactory = $allocationModelFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $websiteId = $this->getCurrentWebsiteId();
        $params = $this->getRequest()->getParams();
        $allocationCollection = $this->_allocationModelFactory->create()->getCollection();
        $allocationCollection->addFieldToFilter('user', $params["current_customer_email"]);
        $allocationCollection->addFieldToFilter('qty', array('gt' => 0));
        $allocationCollection->addFieldToFilter('website_id', $websiteId);
        if($params["sku"]){
            $allocationCollection->addFieldToFilter('sku', array('like' => '%'.$params["sku"].'%'));
        }
        if($params["name"]){
            $allocationCollection->addFieldToFilter('product_name', array('like' => '%'.$params["name"].'%'));
        }
        if($params["from_quantity"]){
            $allocationCollection->addFieldToFilter('qty', array('gteq' => $params["from_quantity"]));
        }
        if($params["to_quantity"]){
            $allocationCollection->addFieldToFilter('qty', array('lteq' => $params["to_quantity"]));
        }
        $result["count"] = $allocationCollection->count();
        $result["message"] = __("Don't have any product allocation match with search.");
        $result["list_product_allocation"] = $allocationCollection->getData();
        $urlBase = $this->getBaseUrl();
        $arrParents = [];
        foreach ($result["list_product_allocation"] as $key => $allocation){
            $allocationHelper = $this->_allocationHelperFactory->create();
            $isProductEnable = $allocationHelper->getIsProductEnable($allocation["sku"],$websiteId);
            $isRuleProductAllocation = $allocationHelper->getRuleProductAllocationBySku($allocation["sku"],$websiteId);
            if (intval($isProductEnable) !== 1 || intval($isRuleProductAllocation) === 0) {
                unset($result["list_product_allocation"][$key]);
                $result["count"]--;
                continue;
            }
            $productHelper = $this->_productHelperFactory->create();
            $productParents = $productHelper->getParentProductBySkuChild($allocation["sku"]);
            $stockRegistry = $this->getStockRegistry();
            $result["list_product_allocation"][$key]['type_product'] = false;
            if($productParents){
                $result["list_product_allocation"][$key]['type_product'] = true;
                $result["html_render"] = '';
                foreach ($productParents as $productId => $productParent) {
                    if (in_array($productId, $arrParents)) {
                        continue;
                    }else{
                        array_push($arrParents,$productId);
                    }
                    $stockRegistry = $productHelper->getStockRegistry();
                    $productStock = $stockRegistry->getStockItem($productId);
                    $loadImageThumbnail = $productHelper->loadImageThumbnail($productParent["product_sku"]);
                    $attributesData = $productHelper->getAttributeOptionsProductConfig($productId);
                    $html = $this->renderHtml($key,$productParent,$attributesData,$loadImageThumbnail,$qtyMinSale = $productStock->getMinSaleQty());
                    $result["list_product_allocation"][$key]['html_render'] = $html;
                }
            }else{
                $productModel = $productHelper->loadProductBySku($allocation["sku"]);
                $productStock = $stockRegistry->getStockItem($productModel->getId());
                $imageThumbnail = $this->loadImageThumbnail($allocation["sku"]);
                $result["list_product_allocation"][$key]['url_product'] = $urlBase.$productModel->getUrlKey().'.html';
                $result["list_product_allocation"][$key]['image_thumbnail'] = $imageThumbnail;
                $result["list_product_allocation"][$key]['min_sale_qty'] = $productStock->getMinSaleQty();
            }
        }
        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }
    public function renderHtml($keyList,$productParent,$attributesData,$loadImageThumbnail,$qtyMinSale){
        $html = '';
        $html .= '<tr class="list-item-'.$keyList.'">';
            $html .= '<td class="list-image-thumbnail"><img src="'.$loadImageThumbnail.'" alt=""/></td>';
            $html .= '<td class="list-product-sku">'.$productParent["product_sku"].'</td>';
            $html .= '<td class="list-product-name">';
                $html .= '<div style="display: block;">';
                    $html .= '<a target="_blank" href="'.$this->getBaseUrl().$productParent["url_key"].'.html'.'">'.$productParent["product_name"].'</a>';
                    $html .= '<div class="field configurable required">';
                        foreach ($attributesData as $key => $attributeProduct){
                            $html .= '<label class="label" for="attribute93">';
                                $html .= ' <span>'.$attributeProduct["label"].'</span>';
                            $html .= '</label>';
                            $html .= '<div class="control">';
                                $html .= '<select class="super-attribute" data-index="'.$keyList.'" name="super_attribute['.$key.']">';
                                $html .= '<option value="">'.__("Choose an option...").'</option>';
                                foreach ($attributeProduct["options"] as $key => $value){
                                    $html .= '<option data-id="'.$value["id"].'" value="'.$value["products"][0].'">'.$value["label"].'</option>';
                                }
                                $html .= '</select>';
                            $html .= '</div>';
                        }
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</td>';
            $html .= '<td class="list-product-allocation-qty">';
                $html .= '<span></span>';
            $html .= '</td>';
            $html .= '<td class="list-action list-action-'.$keyList.'">';
                $html .= '<div class="list-action-content">';
                    $html .= '<input class="input-add-to-cart input-add-my-orders" readonly id="qty_to_cart_" name="qty_to_cart[]" min="'.$qtyMinSale.'" value="'.$qtyMinSale.'">';
                    $html .= '<a href="javascript:void(0)" class="add-to-cart button disabled" data-index="'.$keyList.'" data-allocation-id=""><span></span></a>';
                $html .= '</div>';
                $html .= '<span class="label label-message">';
                $html .= '</span>';
            $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }
    public function loadImageThumbnail($sku)
    {
        $productHelper = $this->_productHelperFactory->create();
        $product = $productHelper->loadProductBySku($sku);
//        $product = $this->loadMyProduct($sku);
        return $this->imageHelper->init($product, 'small_image')
            ->setImageFile($product->getSmallImage()) // image,small_image,thumbnail
            ->resize(80,80)
            ->getUrl();
//        return $this->imageHelper->init($product, 'product_page_image_small')->setImageFile($product->getFile())->resize(80,80)->getUrl();
    }
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
    public function getStockRegistry()
    {
        return $this->stockRegistry;
    }
    public function getCurrentWebsiteId(){
        return $this->storeManager->getStore()->getWebsiteId();
    }

    public function getAttributeOptionsProductConfig(int $id)
    {
        $productHelper = $this->_productHelperFactory->create();
        $parentProduct = $productHelper->loadProductById($id);
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
}