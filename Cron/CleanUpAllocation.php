<?php
namespace Commercers\ProductAllocation\Cron;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ManagerInterface as EventManager;
class CleanUpAllocation
{
    protected $_pageFactory;
    protected $coreRegistry;
    protected $resultPageFactory;
    protected $customerSession;
    protected $_allocationModelFactory;
    protected $_allocationHelperFactory;
    protected $_historyHelperFactory;
    protected $_websiteModel;
    protected $productRepository;
    protected $storeManager;
    protected $productCollectionFactory;
    protected $_scopeHelperFactory;
    protected $customerGroupCollection;
    protected $customerCollectionFactory;
    protected $customerAccountManagement;
    protected $_productModel;
    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Commercers\ProductAllocation\Helper\ScopeFactory $scopeHelperFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\Website $websiteModel,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        $this->_productModel = $productModel;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->_scopeHelperFactory =$scopeHelperFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->_websiteModel = $websiteModel;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_allocationModelFactory = $allocationModelFactory;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->customerSession = $customerSession;
        $this->_pageFactory = $pageFactory;
    }

    public function execute()
    {
        $helperAllocation = $this->_allocationHelperFactory->create();
        if (!intval($helperAllocation->isEnabledCron())) {
            return;
        }
        $this->createProductAllocationByCron();
        $this->cleanUpProductAllocationByCron();
        return;
    }
    protected function cleanUpProductAllocationByCron(){
        $allocationCollection = $this->_allocationModelFactory->create()->getCollection();
        foreach ($allocationCollection->getData() as $allocation) {
            $historyHelper = $this->_historyHelperFactory->create();
            $allocationModel = $this->_allocationModelFactory->create();
            $helperAllocation = $this->_allocationHelperFactory->create();
            $websiteId = $allocation["website_id"];
            $websiteName = $this->getWebsiteName($websiteId);
            $allocationModel->load($allocation["allocation_id"]);
            if (!$helperAllocation->isEnabled($websiteId)) {
                $logActionMessage = __('Cron Log: Product SKU ' . $allocationModel["sku"] . ' is deleted in system because' . $websiteName . ' ' . 'is disabled');
                $historyHelper->writeLogData($websiteId, $logActionMessage, $allocationModel["user"], $allocationModel["sku"], $orderId = null, $orderQty = null, $logFromQty = null, $logToQty = null);
                $isHasWarning = true;
                $allocationModel->delete();
                continue;
            }
            if(!$this->isProductExist($allocationModel["sku"])){
                $logActionMessage = __('Cron Log: Product SKU '.$allocationModel["sku"].' is not existed in system');
                $historyHelper->writeLogData($websiteId,$logActionMessage,$allocationModel["user"],$allocationModel["sku"],$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                $isHasWarning = true;
                $allocationModel->delete();
                continue;
            }
            $ruleProductAllocation = $this->getRuleProductAllocationBySku($allocationModel->getSku(), $websiteId);
            if (!$ruleProductAllocation) {
                $logActionMessage = __('Cron Log: Product SKU ' . $allocationModel["sku"] . ' is deleted in system because product no allocation');
                $historyHelper->writeLogData($websiteId, $logActionMessage, $allocationModel["user"], $allocationModel["sku"], $orderId = null, $orderQty = null, $logFromQty = null, $logToQty = null);
                $isHasWarning = true;
                $allocationModel->delete();
                continue;
            }
            $isProductEnable = $this->getIsProductEnable($allocationModel["sku"]);
            if (intval($isProductEnable) == 2) {
                $logActionMessage = __('Cron Log: Product SKU ' . $allocationModel["sku"] . ' is deleted in system because product disabled');
                $historyHelper->writeLogData($websiteId, $logActionMessage, $allocationModel["user"], $allocationModel["sku"], $orderId = null, $orderQty = null, $logFromQty = null, $logToQty = null);
                $isHasWarning = true;
                $allocationModel->delete();
                continue;
            }
            $isEmailNotExists = $this->emailExistOrNot($allocationModel['user'],$websiteId);
            if($isEmailNotExists){
                $logActionMessage = __('Cron Log: Customer Email '.$allocationModel['user'].' is not existed in system');
                $historyHelper->writeLogData($websiteId,$logActionMessage,$allocationModel['user'],$allocationModel['sku'],$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                $isHasWarning = true;
                $allocationModel->delete();
                continue;
            }
        }
        return;
    }
    protected function createProductAllocationByCron(){
        $scopes = $this->_scopeHelperFactory->create()->getScopeOptionHash();
        foreach($scopes as $scopeId => $scopeName){
            $productCollection = $this->getProductCollection($scopeId);
            $customerCollection = $this->getCustomerCollection($scopeId);
            foreach ($productCollection as $product){
                $productAllocationRule = $this->getRuleProductAllocationBySku($product->getSku(),$scopeId);
                if(!$productAllocationRule){
                    continue;
                }
                foreach ($customerCollection as $customer){
                    $productAllocationModel = $this->_allocationModelFactory->create();
                    $allocation = $productAllocationModel->getDataAllocation($product["sku"],$customer["email"],$scopeId);
                    if($allocation->getAllocationId()) {
                        continue;
                    }
                    $groupCollection = $this->customerGroupCollection->load($customer["group_id"]);
                    $userGroup = $groupCollection->getCustomerGroupCode();
                    $allocationType = '';
                    if($productAllocationRule == 1){
                        $allocationType = 'Individual Allocation';
                    }
                    $productAllocationModel->addData([
                        'website_id' => $scopeId,
                        'product_name' => $product->getName(),
                        'sku' => $product->getSku(),
                        'user' => $customer["email"],
                        'qty' => 0,
                        'user_group' => $userGroup,
                        'allocation_type' => $allocationType
                    ])->save();
                    $historyHelper = $this->_historyHelperFactory->create();
                    $logActionMessage = __('Cron Log: Product SKU '.$product->getSku().' Created Product Allocation Success');
                    $historyHelper->writeLogData($scopeId,$logActionMessage,$customer["email"],$product->getSku(),$orderId=null,$orderQty=null,$logFromQty=0,$logToQty=0);
                }
            }
        }
        return;
    }

    protected function getRuleProductAllocationBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }
    protected function getWebsiteName($websiteId){
        $collection = $this->_websiteModel->load($websiteId,'website_id');
        return $collection->getName();
    }
    protected function getStoreIdByWebsiteId(int $websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    protected function getIsProductEnable($sku)
    {
        $_product = $this->productRepository->get($sku);
        return $_product->getStatus();
        // 2 means Disabled , 1 means Enabled
    }
    public function isProductExist($sku)
    {
        return $this->_productModel->getIdBySku($sku);
    }
    protected function getProductCollection($websiteId)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addWebsiteFilter($websiteId);
        return $collection;
    }
    protected function getCustomerCollection($websiteId) {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter("website_id", array("eq" => $websiteId));
        return $collection;
    }
    public function emailExistOrNot($email,$websiteId)
    {
        return $this->customerAccountManagement->isEmailAvailable($email,$websiteId);
    }
}
