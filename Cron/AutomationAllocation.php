<?php
namespace Commercers\ProductAllocation\Cron;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ManagerInterface as EventManager;
class AutomationAllocation
{
    const VALUE_GROUP_QTY_NOT_ASSIGNED = 1;
    const VALUE_INDIVIDUAL = 1;
    const VALUE_CUSTOMER_GROUP = 2;
    protected $_automationHelperFactory;
    protected $productCollectionFactory;
    protected $storeManager;
    protected $productRepository;
    protected $_allocationFactory;
    protected $_individualCount;
    private $_resourceConnection;
    protected $stockRegistry;
    protected $_historyHelperFactory;
    protected $_scopeHelperFactory;
    protected $customerModel;
    protected $_customerGroupCollection;
    protected $_customerGroupColl;
    protected $customerCollectionFactory;
    private $getSalableQuantityDataBySku;
    public function __construct(
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupColl,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Magento\Customer\Model\Customer $customerModel,
        \Commercers\ProductAllocation\Helper\ScopeFactory $scopeHelperFactory,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Commercers\ProductAllocation\Model\Service\Group\Allocation\Count\Individual $individualCount,
        \Commercers\ProductAllocation\Helper\AutomationFactory $automationHelperFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory
    )
    {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->_customerGroupColl = $customerGroupColl;
        $this->_customerGroupCollection = $customerGroupCollection;
        $this->customerModel = $customerModel;
        $this->_scopeHelperFactory =$scopeHelperFactory;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->stockRegistry = $stockRegistry;
        $this->_individualCount = $individualCount;
        $this->_automationHelperFactory = $automationHelperFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->_allocationFactory = $allocationFactory;
        $this->_resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $helperAutomation = $this->_automationHelperFactory->create();
        if (!intval($helperAutomation->isEnabled())) {
            return;
        }
        if (!intval($helperAutomation->isEnabledAutomation())) {
            return;
        }
        $scopes = $this->_scopeHelperFactory->create()->getScopeOptionHash();
        $assignTo = $helperAutomation->isFieldAssignToAutomation();
        $customerGroupsStr = $helperAutomation->isFieldCustomerGroupAutomation();
        $customerGroups = explode(",", $customerGroupsStr);
        foreach($scopes as $scopeId => $scopeName){
            //Please check again
//            if($scopeId == 2){
//                continue;
//            }
            $productCollection = $this->getProductCollection($scopeId);
            foreach ($productCollection as $product){
                $productAllocationRule = $this->getRuleProductAllocationBySku($product->getSku(),$scopeId);
                if(!$productAllocationRule){
                    continue;
                }
                $isSimpleProduct = $this->isSimpleProduct($product->getSku());
                if(!$isSimpleProduct){
                    continue;
                }
                $currentSalableQuantity = (int)round($this->getSalableQuantityBySku($product->getSku()));
                $allocationOwnerEmail = $this->getAllocationOwnerBySku($product->getSku(),$scopeId);
                if($assignTo == self::VALUE_INDIVIDUAL){
                    $calculateQtyProductAllocated = $this->calculateQtyProductAllocated($product->getSku(),$allocationOwnerEmail);
                    $qtyNoAssigned = $currentSalableQuantity-$calculateQtyProductAllocated;
                    if($qtyNoAssigned <= 0){
                        $qtyNoAssigned = 0;
                    }
                    $customerModel = $this->customerModel->setWebsiteId($scopeId)->loadByEmail($allocationOwnerEmail);
                    if (!$customerModel->getId()) {
                        continue;
                    }
                    $userGroup = $customerModel["group_id"];
                    if(empty($allocationOwnerEmail)){
                        continue;
                    }
                    // The distribution of allocation not yet assigned
                    $productAllocationModel = $this->_allocationFactory->create();
                    $productAllocationCollection = $productAllocationModel->getDataAllocation(trim($product->getSku()),trim($allocationOwnerEmail),trim($scopeId));
                    $allocationType = 'Individual Allocation';
                    $logFromQty = 0;
                    if($productAllocationCollection->getAllocationId()){
                        $logFromQty += $productAllocationCollection->getQty();
                        $logToQty = trim($qtyNoAssigned);
                        $productAllocationCollection->setQty($logToQty);
                        $productAllocationCollection->setGroupQtyNotAssigned(self::VALUE_GROUP_QTY_NOT_ASSIGNED);
                        // check if the quantity has changed, if yes save it
                        $productAllocationCollection->save();
                    }else{
                        $logToQty = trim($qtyNoAssigned);
                        $productAllocationCollection->setGroupQtyNotAssigned(self::VALUE_GROUP_QTY_NOT_ASSIGNED);
                        $productAllocationCollection->setProductName($product->getName());
                        $productAllocationCollection->setWebsiteId($scopeId);
                        $productAllocationCollection->setUser($allocationOwnerEmail);
                        $productAllocationCollection->setSku($product->getSku());
                        $productAllocationCollection->setQty($logToQty);
                        $productAllocationCollection->setUserGroup($userGroup);
                        $productAllocationCollection->setAllocationType(trim($allocationType));
                        $productAllocationCollection->save();
                    }
                    $logActionMessage = __('Cron Log: Allocation Automation Success.');
                    $historyHelper = $this->_historyHelperFactory->create();
                    $historyHelper->writeLogData($scopeId,$logActionMessage,$allocationOwnerEmail,$product->getSku(),$orderId=null,$orderQty=null,$logFromQty,$logToQty);
                }elseif($assignTo == self::VALUE_CUSTOMER_GROUP){
                    if(!$customerGroups){
                        continue;
                    }
//                    $allocationType = 'Individual Allocation';
                    foreach ($customerGroups as $customerGroupId){
                        $calculateQtyProductAllocatedGroup = (int)round($this->calculateQtyProductAllocatedGroup($product->getSku(),$customerGroupId));
                        $qtyNoAssignedGroup = $currentSalableQuantity-$calculateQtyProductAllocatedGroup;
                        if($qtyNoAssignedGroup <= 0){
                            $qtyNoAssignedGroup = 0;
                        }
                        $customerCollection = $this->getCustomerCollectionByGroupId($customerGroupId,$scopeId);
                        foreach ($customerCollection as $customer){
                            $customerEmail = $customer->getEmail();
                            // The distribution of allocation not yet assigned
                            $productAllocationModel = $this->_allocationFactory->create();
                            $productAllocationCollection = $productAllocationModel->getDataAllocation(trim($product->getSku()),trim($customerEmail),trim($scopeId));
                            $allocationType = 'Individual Allocation';
                            $logFromQty = 0;
                            if($productAllocationCollection->getQty()){
                                $logFromQty += $productAllocationCollection->getQty();
                            }
                            $logToQty = trim($qtyNoAssignedGroup);
                            if($productAllocationCollection->getAllocationId()){
                                $productAllocationCollection->setQty(trim($qtyNoAssignedGroup));
                                $productAllocationCollection->setGroupQtyNotAssigned(self::VALUE_GROUP_QTY_NOT_ASSIGNED);
                            }else{
                                $productAllocationCollection->setGroupQtyNotAssigned(self::VALUE_GROUP_QTY_NOT_ASSIGNED);
                                $productAllocationCollection->setProductName($product->getName());
                                $productAllocationCollection->setWebsiteId($scopeId);
                                $productAllocationCollection->setUser($customerEmail);
                                $productAllocationCollection->setSku($product->getSku());
                                $productAllocationCollection->setQty(trim($qtyNoAssignedGroup));
                                $productAllocationCollection->setUserGroup($customer->getGroupId());
                                $productAllocationCollection->setAllocationType(trim($allocationType));
                            }
                            $productAllocationCollection->save();
                            $logActionMessage = __('Cron Log: Allocation Automation Success.');
                            $historyHelper = $this->_historyHelperFactory->create();
                            $historyHelper->writeLogData($scopeId,$logActionMessage,$customerEmail,$product->getSku(),$orderId=null,$orderQty=null,$logFromQty,$logToQty);
                        }
                    }
                }
            }
        }
        return;
    }

    protected function getCustomerCollectionByGroupId($groupId,$websiteId) {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter("group_id", array("eq" => $groupId));
        $collection->addAttributeToFilter("website_id", array("eq" => $websiteId));
        return $collection;
    }
    protected function getCustomerGroupName($groupId)
    {
        $collection = $this->_customerGroupCollection->load($groupId);
        return $collection->getCustomerGroupCode();//Get current customer group name
    }
    protected function getProductCollection($websiteId)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addWebsiteFilter($websiteId);
        return $collection;
    }
    protected function getRuleProductAllocationBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }
    protected function getStoreIdByWebsiteId(int $websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }
    protected function getAllocationOwnerBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'allocation_owner',$storeId); //change attribute_code
        return $attributeValue;
    }
    protected function calculateQtyProductAllocated($sku,$email){
        $results = [];
        $allocations = $this->_allocationFactory->create()->getCollection();
        $allocations->addFieldToFilter('sku', $sku);
        $allocations->addFieldToFilter('user', array('neq' => $email));
        $countAllocation = $allocations->count();
        $allocations->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $allocations->getSelect()->columns(array('sum' => 'SUM(qty)'));
        $readConnection  = $this->_resourceConnection->getConnection();
        $sqlResult = $readConnection->fetchRow($allocations->getSelect());
        $data["count"] = (int)$sqlResult['sum'];
        return $data["count"];
    }
    protected function calculateQtyProductAllocatedGroup($sku,$groupId){
        $results = [];
        $allocations = $this->_allocationFactory->create()->getCollection();
        $allocations->addFieldToFilter('sku', $sku);
        $allocations->addFieldToFilter('user_group', array('neq' => $groupId));
        $countAllocation = $allocations->count();
        $allocations->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $allocations->getSelect()->columns(array('sum' => 'SUM(qty)'));
        $readConnection  = $this->_resourceConnection->getConnection();
        $sqlResult = $readConnection->fetchRow($allocations->getSelect());
        $data["count"] = (int)$sqlResult['sum'];
        return $data["count"];
    }
    protected function getQuantityStockQty($sku) {
        return $this->stockRegistry->getStockItemBySku($sku)->getQty();
    }
    protected function getSalableQuantityBySku($sku) {
        $salable = $this->getSalableQuantityDataBySku->execute($sku);
        return $salable[0]["qty"];
    }
    public function isSimpleProduct($sku)
    {
        $isTypeSimple = false;
        try {
            $product = $this->productRepository->get($sku);
            $isTypeSimple = $product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        } catch (\Exception $exception) {
            throw new NoSuchEntityException(__('Product doesn\'t exist'));
        }
        return $isTypeSimple;
    }
}
