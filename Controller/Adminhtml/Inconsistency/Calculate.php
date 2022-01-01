<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Inconsistency;
use Commercers\OrderApproval\Helper\Scope;
use Commercers\ProductAllocation\Model\Allocation;
use Magento\Framework\App\Filesystem\DirectoryList;
use Commercers\ProductAllocation\Model\EavInstall;
use Magento\Framework\Exception\LocalizedException;
class Calculate extends \Magento\Backend\App\Action
{
    protected $_storeManager;
    protected $registry;
    protected $eavInstall;
    protected $_productCollectionFactory;
    protected $_marketingFactory;
    protected $_scopeHelperFactory;
    protected $_customerFactory;
    protected $_allocationFactory;
    protected $stockState;
    protected $_product;
    protected $_inconsistencyFactory;
    protected $_resource;
    private $getSalableQuantityDataBySku;
    public function __construct(
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \Magento\Framework\App\ResourceConnection $resource,
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Commercers\ProductAllocation\Helper\ScopeFactory $scopeHelperFactory,
        \Commercers\ProductAllocation\Model\Service\Group\Allocation\Count $countSevice,
        \Commercers\ProductAllocation\Helper\MarketingFactory $marketingFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->_resource = $resource;
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->_product = $product;
        $this->stockState = $stockState;
        $this->_allocationFactory = $allocationFactory;
        $this->_customerFactory = $customerFactory;
        $this->_scopeHelperFactory =$scopeHelperFactory;
        $this->_countSevice = $countSevice;
        $this->_marketingFactory = $marketingFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManagerInterface;
        $this->registry = $registry;
        parent::__construct($context);
    }
    public function execute()
    {
        $connection = $this->_resource->getConnection();
        $tableName = 'commercers_product_allocation_inconsistency';
        $connection->truncateTable($tableName);
        $scopes = $this->_scopeHelperFactory->create()->getScopeOptionHash();
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            foreach($scopes as $scopeId => $scopeName){
                //Please check again
                if($scopeId == 1){
                    $allocations = $this->getData();
                    if($allocations){
                        foreach($allocations as $allocation){
                            if(!$allocation["sku"]){
                                continue;
                            }
                            $marketingHelper = $this->_marketingFactory->create();
                            $bfMarketingGroups = $marketingHelper->getMarketingGroups();
                            $result = $this->_countSevice->countServiceGroupAllocation($bfMarketingGroups,$allocation["entity_id"],$scopeId);
                            $isSimpleProduct = $this->isSimpleProduct($allocation["sku"]);
                            if(!$isSimpleProduct){
                                continue;
                            }
                            $currentSalableQuantity = (int)round($this->getSalableQuantityBySku($allocation["sku"]));
                            //Please look again
                            if($allocation["value"]){
                                $count = $result['count'];
                            }else if(!$allocation["value"]){
                                $count = $currentSalableQuantity;
                            }
                            if($currentSalableQuantity > $result['count']){
                                if(!$allocation["value"]){
                                    $marketingQuantityUpdate = ($currentSalableQuantity > $result['count']) ? abs($result['count'] - $currentSalableQuantity) : 0;
                                }else if($allocation["value"]){
                                    $marketingQuantityUpdate = $currentSalableQuantity;
                                }
                            }else{
                                if(!$allocation["value"]){
                                    $marketingQuantityUpdate = 0;
                                }else if($allocation["value"]){
                                    $marketingQuantityUpdate = $currentSalableQuantity;
                                }
                            }
                            $typeProduct = $this->_product->getById($allocation["entity_id"])->getTypeId();
                            if(!$allocation["value"]){
                                $result['info'] = $result['info']."MARKETING GROUP (". $marketingQuantityUpdate .")";
                                $this->updateMarketingAllocation($allocation["sku"], $marketingQuantityUpdate , $scopeId = null);
                            }else if($allocation["value"]){
                                $marketingQuantityUpdate = $currentSalableQuantity;
                            }
                            // $diff = $currentSalableQuantity - $count;
                            if($currentSalableQuantity != $count && $typeProduct == "simple"){
                                $productName = $this->_product->getById($allocation["entity_id"])->getName();
                                $inconsistency = $this->_inconsistencyFactory->create();
                                $inconsistency->addData(array(
                                    'website_id' => $scopeId,
                                    'allocation' => $count,
                                    'quantity' => $currentSalableQuantity,
                                    'product_id' => $allocation["entity_id"],
                                    'product_name' => $productName,
                                    'sku' => $allocation["sku"],
                                    'difference' => $currentSalableQuantity - $count,
                                    'information' => $result['info'],
                                    'type' => $typeProduct
                                ))
                                    ->save()
                                ;
                            }
                        }
                    }
                }
            }
            $this->messageManager->addSuccessMessage(__('Calculated Inconsistency Product Allocation Success.'));
        } catch (LocalizedException $e) {
            echo $e->getMessage();exit;
            $this->messageManager->addErrorMessage(__('Calculated Inconsistency Product Allocation Error.'));
        }
        return $resultRedirect->setPath('productallocation/inconsistency/');
    }
    public function isSimpleProduct($sku)
    {
        $isTypeSimple = false;
        try {
            $product = $this->_product->get($sku);
            $isTypeSimple = $product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        } catch (\Exception $exception) {
            throw new NoSuchEntityException(__('Product doesn\'t exist'));
        }
        return $isTypeSimple;
    }
    protected function getSalableQuantityBySku($sku) {
        $salable = $this->getSalableQuantityDataBySku->execute($sku);
        return $salable[0]["qty"];
    }
    public function getData()
    {
        // Set StoreId = 0; Please Check again
        $storeId = 0;
        $collection = $this->_productCollectionFactory->create();
        $commercersProductAllocationTable = "commercers_product_allocation";
        $collection->getSelect()
            ->joinRight(
                array('allocation' =>$commercersProductAllocationTable),
                'e.sku= allocation.sku',
                array()
            );
        $collection->getSelect()
            ->joinLeft(
                array('product_varchar' =>'catalog_product_entity_varchar'),
                'product_varchar.entity_id = e.entity_id',
                array('value')
            );
        $attributeProductName = '73';
        $collection->getSelect()->where('product_varchar.attribute_id = ?', $attributeProductName);
        $collection->getSelect()->where('product_varchar.store_id = ?', $storeId);
        $collection->getSelect()->group('e.entity_id');
        return $collection->getData();
    }
}
