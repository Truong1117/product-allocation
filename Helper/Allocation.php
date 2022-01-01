<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Allocation extends AbstractHelper
{
    const XML_PATH_ENABLED = 'productallocation/general/config';
    const XML_PATH_CRON_ENABLED = 'productallocation/cron_clean_up_allocation/config';
    const XML_PATH_AUTOMATION_ENABLED = 'productallocation/automation/config';
    const XML_PATH_IS_CREDITBACK_CONFIG_ENABLED = 'productallocation/settings/creditbackconfigure_enabled';
    const XML_PATH_CREDITBACK_STATUSES = 'productallocation/settings/creditbackstatus';
    protected $storeManager;
    protected $_dataFactory;
    protected $scopeConfig;
    protected $productRepository;
    protected $_allocationFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Commercers\ProductAllocation\Helper\DataFactory $dataFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_allocationFactory = $allocationFactory;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->_dataFactory = $dataFactory;
        $this->storeManager = $storeManagerInterface;
    }

    public function isEnabled($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_ENABLED,$websiteId);
    }

    public function getConfigValue($path,$websiteId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
    public function isEnabledCron($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CRON_ENABLED,$websiteId);
    }

    public function isEnabledAutomation($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_AUTOMATION_ENABLED,$websiteId);
    }

    public function getRuleProductAllocationBySku($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->get($sku);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }

    public function getRuleProductAllocationById($productId,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $_product = $this->productRepository->getById($productId);
        $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'rule_product_allocation',$storeId); //change attribute_code
        return $attributeValue;
    }

    public function getStoreIdByWebsiteId(int $websiteId)
    {
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    public function getIsProductEnable($sku,$websiteId)
    {
        $storeId = $this->getStoreIdByWebsiteId($websiteId);
        $attributeValue = null;
        try {
            $_product = $this->productRepository->get($sku);
            $attributeValue = $_product->getResource()->getAttributeRawValue($_product->getId(),'status',$storeId); //change attribute_code
        } catch (Exception $e) {
            $attributeValue = null;
        }
        return $attributeValue;
    }

    public function getProductAllocation($email,$sku,$websiteId){
        $productAllocationModel = $this->_allocationFactory->create()->getCollection();
        $productAllocationModel->addFieldToFilter('sku', $sku);
        $productAllocationModel->addFieldToFilter('user', $email);
        $productAllocationModel->addFieldToFilter('website_id', $websiteId);
        return $productAllocationModel->getFirstItem()->getData();
    }

}

