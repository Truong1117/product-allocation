<?php

namespace Commercers\ProductAllocation\Model;

use Commercers\ProductAllocation\Model\ResourceModel\Allocation\Collection;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
class  EavInstall extends EavSetup {

    const DELIMITER_FOR_CSV_EXPORT = 'productallocation/general/delimiter_csv_export';
    protected $csvReader;
    protected $_allocationFactory;
    protected $_resource;
    protected $productRepository;
    protected $customerRepository;
    protected $_registry;
    protected $scopeConfig;
    protected $_historyHelperFactory;
    protected $storeManager;
    protected $customerModel;
    protected $_customerGroupCollection;
    protected $authSession;
    protected $_allocationHelperFactory;
    public function __construct(
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory,
        \Magento\Framework\File\Csv $csvReaderFactory
    ) {
        parent::__construct($setup, $context, $cache, $attrGroupCollectionFactory);
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->authSession = $authSession;
        $this->_customerGroupCollection = $customerGroupCollection;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_registry = $registry;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->_resource = $resource;
        $this->_allocationFactory = $allocationFactory;
        $this->csvReader = $csvReaderFactory;
    }
    public function addProductAllocation($filePath,$paramOptionWebsiteId)
    {
        if(!$this->readCsvData($filePath)){
            return false;
        }
        $csvData = $this->readCsvData($filePath);
        $exceptions = [];
        $success = [];
        $count=0;
        $websiteId =$this->storeManager->getStore()->getWebsiteId();
        $currentUser = $this->authSession->getUser();
        $fullNameUser = $currentUser->getLastname().' '.$currentUser->getFirstname();
        $logWebsite = $paramOptionWebsiteId;
        foreach($csvData as $key => $csvLine){
            $count++;
            $logUser = trim($csvLine["user"]);
            $logSku = trim($csvLine["sku"]);
//            $logWebsite = trim($csvLine['website_id']);
            $customerModel = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($logUser);
            $userGroup = $customerModel["group_id"];
            try {
                $logActionMessage = '';
                $helperAllocation = $this->_allocationHelperFactory->create();
                $historyHelper = $this->_historyHelperFactory->create();
                if (!$helperAllocation->isEnabled($logWebsite)) {
                    $logActionMessage = __('Imported Add Error: Product allocation is not allowed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $productAllocationRule = $this->getRuleProductAllocationBySku($logSku,$logWebsite);
                if(!$productAllocationRule){
                    $exceptions[] = __('Error For Row '.$count.': Product SKU '.$csvLine["sku"].' is not allocation in system');
                    $logActionMessage = __('Imported Add Error: Product SKU '.$csvLine["sku"].' is not allocation in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $product = $this->loadMyProduct($csvLine["sku"]);
                if(empty($product)){
                    $exceptions[] = __('Error For Row '.$count.': Product SKU '.$csvLine["sku"].' is not existed in system');
                    $logActionMessage = __('Imported Add Error: Product SKU '.$csvLine["sku"].' is not existed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $customer = $this->getCustomer(trim($csvLine["user"]));
                if(!$customer->getId()){
                    $exceptions[] = __('Warning For Row '.$count.': Customer Email '.$csvLine["user"].' is not existed in system');
                    $logActionMessage = __('Imported Add Error: Customer Email '.$csvLine["user"].' is not existed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $productAllocationModel = $this->_allocationFactory->create();
                $productAllocationCollection = $productAllocationModel->getDataAllocation(trim($csvLine["sku"]),trim($csvLine["user"]),trim($logWebsite));
                //Check again
                $allocationType = '';
                if($productAllocationRule == 1){
                    $allocationType = 'Individual Allocation';
                }
                $logFromQty = 0;
                if($productAllocationCollection->getAllocationId()){
                    $logFromQty += $productAllocationCollection->getQty();
                    $newQuantity = $productAllocationCollection->getQty() + trim($csvLine["qty"]);
                    if($newQuantity < 0) {
                        $newQuantity = 0;
                    }
                    $logToQty = $newQuantity;
                    $productAllocationCollection->setQty($newQuantity);
                    // check if the quantity has changed, if yes save it
                    $productAllocationCollection->save();
                    $logActionMessage = __('Add Imported Success By'.$fullNameUser);
                    $success[] = __("Row #".$count." allocations were updated.");
                }else{
                    if(!($csvLine["qty"])){
                        $csvLine["qty"] = 0;
                    }
                    $logToQty = trim($csvLine["qty"]);
                    $productAllocationCollection->setProductName($product->getName());
//                    $productAllocationCollection->setWebsiteId($csvLine['website_id']);
                    $productAllocationCollection->setWebsiteId($logWebsite);
                    $productAllocationCollection->setUser($csvLine["user"]);
                    $productAllocationCollection->setSku($csvLine["sku"]);
                    $productAllocationCollection->setQty(trim($csvLine["qty"]));
                    $productAllocationCollection->setUserGroup(trim($userGroup));
                    $productAllocationCollection->setGroupQtyNotAssigned(0);
                    $productAllocationCollection->setAllocationType(trim($allocationType));
                    $validationResult = $productAllocationCollection->isValidForImport();
                    if($validationResult == true) {
                        $productAllocationCollection->save();
                        $logActionMessage = __('Add Imported Success By '.$fullNameUser);
                        $success[]  = __("Row #".$count." allocations were imported.");
                    }else{
                        $exceptions[] = __( 'Invalid Data In Row #'.$count.':'.implode(' ',$validationResult));
                    }
                }
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty,$logToQty);
            }catch(\Exception $e){
                $logActionMessage = __('Imported Error: ' . $e->getMessage());
                $exceptions[] = __( 'Error For Row #'.$count.':'.$e->getMessage());
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
            }
        }
        if(count($success) >= 1){
            $data["success"] = true;
            $data["success_message"] = 'Imported successfully. '.count($success).' lines imported';
        }else{
            $data["success"] = false;
            $data["exceptions"] = $exceptions;
        }
        $this->_registry->register('pl_has_warning', '$isHasWarning');
        return $data;
    }
    public function replaceProductAllocation($filePath,$paramOptionWebsiteId)   {
        if(!$this->readCsvData($filePath)){
            return false;
        }
        $csvData = $this->readCsvData($filePath);
        $exceptions = [];
        $success = [];
        $count=0;
        $websiteId =$this->storeManager->getStore()->getWebsiteId();
        $currentUser = $this->authSession->getUser();
        $fullNameUser = $currentUser->getLastname().' '.$currentUser->getFirstname();
        $logWebsite = $paramOptionWebsiteId;
        foreach($csvData as $key => $csvLine){
            $count++;
            $logUser = trim($csvLine["user"]);
            $logSku = trim($csvLine["sku"]);
//            $logWebsite = trim($csvLine['website_id']);
            $customerModel = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($logUser);
            $userGroup = $customerModel["group_id"];
            try {
                $logActionMessage = '';
                $helperAllocation = $this->_allocationHelperFactory->create();
                $historyHelper = $this->_historyHelperFactory->create();
                if (!$helperAllocation->isEnabled($logWebsite)) {
                    $logActionMessage = __('Imported Add Error: Product allocation is not allowed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $productAllocationRule = $this->getRuleProductAllocationBySku($logSku,$logWebsite);
                if(!$productAllocationRule){
                    $exceptions[] = __('Error For Row '.$count.': Product SKU '.$csvLine["sku"].' is not allocation in system');
                    $logActionMessage = __('Imported Replace Error: Product SKU '.$csvLine["sku"].' is not allocation in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $product = $this->loadMyProduct($csvLine["sku"]);
                if(empty($product)){
                    $exceptions[] = __('Error For Row '.$count.': Product SKU '.$csvLine["sku"].' is not existed in system');
                    $logActionMessage = __('Imported Replace Error: Product SKU '.$csvLine["sku"].' is not existed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $customer = $this->getCustomer(trim($csvLine["user"]));
                if(!$customer->getId()){
                    $exceptions[] = __('Warning For Row '.$count.': Customer Email '.$csvLine["user"].' is not existed in system');
                    $logActionMessage = __('Imported Replace Error: Customer Email '.$csvLine["user"].' is not existed in system');
                    $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
                    continue;
                }
                $productAllocationModel = $this->_allocationFactory->create();
                $productAllocationCollection = $productAllocationModel->getDataAllocation(trim($csvLine["sku"]),trim($csvLine["user"]),trim($logWebsite));
                //Check again
                $allocationType = '';
                if($productAllocationRule == 1){
                    $allocationType = 'Individual Allocation';
                }
                $logFromQty = 0;
                if($productAllocationCollection->getAllocationId()){
                    $logFromQty += $productAllocationCollection->getQty();
                    $newQuantity = trim($csvLine["qty"]);
                    if($newQuantity < 0) {
                        $newQuantity = 0;
                    }
                    $logToQty = $newQuantity;
                    $productAllocationCollection->setQty($newQuantity);
                    // check if the quantity has changed, if yes save it
                    $productAllocationCollection->save();
                    $logActionMessage = __('Replace Imported Success By '.$fullNameUser);
                    $success[] = __("Row #".$count." allocations were updated.");
                }else{
                    if(!($csvLine["qty"])){
                        $csvLine["qty"] = 0;
                    }
                    $logToQty = trim($csvLine["qty"]);
                    $productAllocationCollection->setProductName($product->getName());
//                    $productAllocationCollection->setWebsiteId($csvLine['website_id']);
                    $productAllocationCollection->setWebsiteId($logWebsite);
                    $productAllocationCollection->setUser($csvLine["user"]);
                    $productAllocationCollection->setSku($csvLine["sku"]);
                    $productAllocationCollection->setQty(trim($csvLine["qty"]));
                    $productAllocationCollection->setUserGroup(trim($userGroup));
                    $productAllocationCollection->setGroupQtyNotAssigned(0);
                    $productAllocationCollection->setAllocationType(trim($allocationType));
                    $validationResult = $productAllocationCollection->isValidForImport();
                    if($validationResult == true) {
                        $productAllocationCollection->save();
                        $logActionMessage = __('Replace Imported Success By '.$fullNameUser);
                        $success[]  = __("Row #".$count." allocations were imported.");
                    }else{
                        $exceptions[] = __( 'Invalid Data In Row #'.$count.':'.implode(' ',$validationResult));
                    }
                }
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty,$logToQty);
            }catch(\Exception $e){
                $logActionMessage = __('Replace By Imported Error: ' . $e->getMessage());
                $exceptions[] = __( 'Error For Row #'.$count.':'.$e->getMessage());
                $historyHelper = $this->_historyHelperFactory->create();
                $historyHelper->writeLogData($logWebsite,$logActionMessage,$logUser,$logSku,$orderId=null,$orderQty=null,$logFromQty=null,$logToQty=null);
            }
        }
//        $connection->commit();
        if(count($success) >= 1){
            $data["success"] = true;
            $data["success_message"] = 'Imported successfully. '.count($success).' lines imported';
        }else{
            $data["success"] = false;
            $data["exceptions"] = $exceptions;
        }
        $this->_registry->register('pl_has_warning', '$isHasWarning');
        return $data;
    }
    public function loadMyProduct($sku)
    {
        return $this->productRepository->get($sku);
    }
    public function getCustomer($email)
    {
        return $this->customerRepository->get($email);
    }
    function readCsvData($filePath){
        $file = fopen($filePath,"r");
        while(!feof($file)){
            $csv[] = fgetcsv($file,0,$this->getDelimiterForCsvExport());
        }
        $keys = array_shift($csv);
        foreach ($csv as $data){
            if(is_array($data)){
                $returnValue[] = array_combine($keys,$data);
            }
        }
        if(isset($returnValue))
            return $returnValue;
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

    public function getDelimiterForCsvExport(){
        return $this->scopeConfig(self::DELIMITER_FOR_CSV_EXPORT);
    }
    protected function scopeConfig($template)
    {
        return $this->scopeConfig->getValue(
            $template,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
