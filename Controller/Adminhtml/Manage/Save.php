<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;

use Commercers\ProductAllocation\Helper\History;

class Save extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $allocationFactory;
    protected $_historyHelperFactory;
    protected $product;
    protected $customerModel;
    protected $storeManager;
    protected $authSession;
    protected $productRepository;
    protected $_customerSession;
    protected $_customerGroupCollection;
    protected $_customerRepositoryInterface;
    protected $_urlInterface;
    protected $_websiteModel;
    protected $_allocationHelperFactory;
    public function __construct(
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Magento\Store\Model\Website $websiteModel,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Group $customerGroupCollection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Catalog\Model\Product $product,
        \Commercers\ProductAllocation\Helper\HistoryFactory $historyHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_websiteModel = $websiteModel;
        $this->_urlInterface = $urlInterface;
        $this->_customerGroupCollection = $customerGroupCollection;
        $this->productRepository = $productRepository;
        $this->authSession = $authSession;
        $this->storeManager = $storeManager;
        $this->customerModel = $customerModel;
       $this->_product = $product;
        $this->_historyHelperFactory = $historyHelperFactory;
        $this->_allocationFactory = $allocationFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $currentUser = $this->authSession->getUser();
        $fullNameUser = $currentUser->getLastname().' '.$currentUser->getFirstname();
        $fromQty = 0;
        $params = $this->getRequest()->getParams();
        try {
            $dataAllocation = $params;
            $allocation = $this->_allocationFactory->create();
            $qtyAdd = 0;
            $allocationType = '';
            $userGroup = null;
            if (isset($params["allocation"]['allocation_id'])) {
                $dataAllocation = $params["allocation"];
                $allocation = $allocation->load($dataAllocation['allocation_id']);
                $allocationType = $allocation->getAllocationType();
                $userGroup = $allocation->getUserGroup();
                $fromQty = $allocation->getQty();
                $websiteId = $allocation->getWebsiteId();
            }else{
                $sku = $this->getRequest()->getParam("sku");
                $email = $this->getRequest()->getParam("user");
                $websiteId = $this->getRequest()->getParam("website_id");
                $websiteName = $this->getWebsiteName($websiteId);
                $helperAllocation = $this->_allocationHelperFactory->create();
                if (!$helperAllocation->isEnabled($websiteId)) {
                    $this->messageManager->addErrorMessage(__('Error: Product Allocation Is Not Allowed In '.$websiteName));
                    return $this->_redirect('productallocation/manage/addaction');
                }
                if(!$this->_product->getIdBySku($sku)) {
                    $this->messageManager->addErrorMessage(__('Error: Product SKU '.$sku.' Is Not Existed In '.$websiteName));
                    return $this->_redirect('productallocation/manage/addaction');
                }
                $customerModel = $this->customerModel->setWebsiteId($websiteId)->loadByEmail($email);
                if (!$customerModel->getId()) {
                    $this->messageManager->addErrorMessage(__('Error: Email '.$email.' Is Not Existed In '.$websiteName));
                    return $this->_redirect('productallocation/manage/addaction');
                }
                $isProductEnable = $this->getIsProductEnable($sku,$websiteId);
                if (intval($isProductEnable) == 2) {
                    $this->messageManager->addErrorMessage(__('Error: Product SKU ' . $sku . ' Is Disabled.'));
                    return $this->_redirect('productallocation/manage/addaction');
                }
                $productAllocationRule = $this->getRuleProductAllocationBySku($sku,$websiteId);
                if(!$productAllocationRule){
                    $this->messageManager->addErrorMessage(__('Error: Product SKU '.$sku.' Is Not Allocation In '.$websiteName));
                    return $this->_redirect('productallocation/manage/addaction');
                }
                //Check again
                if($productAllocationRule == 1){
                    $allocationType = 'Individual Allocation';
                }
                $userGroup = $customerModel["group_id"];
//                $groupCollection = $this->_customerGroupCollection->load($customerModel["group_id"]);
//                $userGroup = $groupCollection->getCustomerGroupCode();
                $productAllocationModel = $this->_allocationFactory->create();
                $allocation = $productAllocationModel->getDataAllocation(trim($sku),trim($email),trim($websiteId));
                if($allocation["allocation_id"]){
                    // Add Sku / Email is exist in product allocation
                    $urlAllocationAdd = $this->getUrl('productallocation/manage/addallocationexists', $params=null);
//                    $this->messageManager->addNoticeMessage(__('There is already an existing user/sku combination for the values entered. The current allocation value is:'.$allocation->getQty().' - You can replace the existing value, add the entered value to the existing qty or cancel your entry'));
                    $this->messageManager->addComplexSuccessMessage(
                        'addProductAllocationWarningMessage',
                        [
                            'message_notice' => 'There is already an existing user/sku combination for the values entered. The current allocation value is: '.$allocation->getQty().' - You can replace the existing value, add the entered value to the existing qty or cancel your entry',
                            'url_replace' => $urlAllocationAdd.'allocation_id/'.$allocation->getAllocationId().'/qty_enter/'.$dataAllocation['qty'].'/action/1/full_name/'.$fullNameUser.'/website_id/'.$websiteId,
                            'url_add' => $urlAllocationAdd.'allocation_id/'.$allocation->getAllocationId().'/qty_enter/'.$dataAllocation['qty'].'/action/2/full_name/'.$fullNameUser.'/website_id/'.$websiteId,
                            'url_cancel' => $urlAllocationAdd.'allocation_id/'.$allocation->getAllocationId().'/qty_enter/'.$dataAllocation['qty'].'/action/3/full_name/'.$fullNameUser.'/website_id/'.$websiteId,
                        ]
                    );
                    return $this->_redirect('productallocation/manage/addaction');
                }
            }
            $productName = $this->loadMyProduct($dataAllocation['sku'])->getName();
            $allocation->addData([
                'website_id' => $websiteId,
                'product_name' => $productName,
                'sku' => $dataAllocation['sku'],
                'user' => $dataAllocation['user'],
                'qty' => $dataAllocation['qty'],
                'user_group' => $userGroup,
                'group_qty_not_assigned' => 0,
                'allocation_type' => $allocationType
            ])->save();
            $historyHelper = $this->_historyHelperFactory->create();
            if (isset($params["allocation"]['allocation_id'])) {
                $this->messageManager->addSuccessMessage(__('Edit Product Allocation Success.'));
                $historyHelper->writeLogData($websiteId,$action='Manage Allocation Edited By '.$fullNameUser,$dataAllocation['user'],$dataAllocation['sku'],$orderId=null,$orderQty=null,$fromQty,$dataAllocation['qty']);
            }else{
                $this->messageManager->addSuccessMessage(__('Created Product Allocation Success.'));
                $historyHelper->writeLogData($websiteId,$action='Manage Allocation Created By '.$fullNameUser,$dataAllocation['user'],$dataAllocation['sku'],$orderId=null,$orderQty=null,$fromQty,$dataAllocation['qty']);
            }
            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect('*/*/editaction', ['allocation_id' => $allocation->getAllocationId(), '_current' => true]);
            }else{
                return $this->_redirect('productallocation/manage/index');
            }
        } catch (Exception $e) {
            //echo $e->getMessage();exit;
            $this->_redirect('*/*/listing');
        }
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
    public function loadMyProduct($sku)
    {
        return $this->productRepository->get($sku);
    }
    protected function getWebsiteName($websiteId){
        $collection = $this->_websiteModel->load($websiteId,'website_id');
        return $collection->getName();
    }
    public function getIsProductEnable($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'status',$storeId); //change attribute_code
        return $attributeValue;
    }
}
