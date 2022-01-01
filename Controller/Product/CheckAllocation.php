<?php

namespace Commercers\ProductAllocation\Controller\Product;

use Commercers\ProductAllocation\Model\Allocation;
use Magento\Framework\App\Action\Action;

class CheckAllocation extends Action{

    protected $_pageFactory;
    protected $_productRepository;
    protected $_customerSession;
    protected $_allocationModelFactory;
    protected $_allocationHelperFactory;
    protected $_dataHelperFactory;
    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Commercers\ProductAllocation\Helper\DataFactory $dataHelperFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_dataHelperFactory = $dataHelperFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_allocationModelFactory = $allocationModelFactory;
        $this->_customerSession = $session;
        $this->_productRepository = $productRepository;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }


    public function execute()
    {
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (!intval($helperAllocation->isEnabled())) {
            $data['message'] = true;
        }else{
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $skuProduct = $this->getRequest()->getParam('skuProduct');
            $enterQty = $this->getRequest()->getParam('enterQty');
            $selectedConfigurableOption = $this->getRequest()->getParam('selectedConfigurableOption');
            if($selectedConfigurableOption){
                $product = $this->loadProductById($selectedConfigurableOption);
                $skuProduct = $product->getSku();
            }
            $data['message'] = true;
            $dataHelper = $this->_dataHelperFactory->create();
            $data['notice'] = __($dataHelper->getMessageError());
            $productAllocationRule = $this->getRuleProductAllocationBySku($skuProduct,$websiteId);
            if($productAllocationRule) {
                $emailCustomer = $this->_customerSession->getCustomer()->getEmail();
                $cart = $this->_checkoutSession->getQuote();
                $result = $cart->getAllItems();
                $itemsIds = array();
                foreach ($result as $cartItem) {
                    if ($cartItem->getProduct()->getSku() == $skuProduct) {
                        $enterQty = $cartItem->getQty() + $enterQty;
                    }
                }
                $allocationsCollection = $this->_allocationModelFactory->create()->getCollection();
                $allocationsCollection->getSelect()->where("user = ?", $emailCustomer);
                $allocationsCollection->getSelect()->where("sku = ?", $skuProduct);
                $allocationsCollection->getSelect()->where("website_id = ?", $websiteId);
                if (!empty($allocationsCollection->getData())) {
                    $allocationQty = $allocationsCollection->getFirstItem()->getQty();
                    if ($allocationQty <= 0 || intval($enterQty) > intval($allocationQty)) {
                        $data['message'] = false;
                        $data['notice'] = __($dataHelper->getMessageError());
                    }
                }else{
                    $data['message'] = false;
                    $data['notice'] = __($dataHelper->getMessageError());
                }
            }
        }  
        try {
            $response = $this->resultFactory
                ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                ->setData($data);
            return $response;
        } catch (Exception $e) {
        }
    }
    public function loadProductById($productId)
    {
        return $this->_productRepository->getById($productId);
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
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }
}
