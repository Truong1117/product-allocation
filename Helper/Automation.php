<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Automation extends AbstractHelper
{
    const XML_PATH_ENABLED = 'productallocation/general/config';
    const XML_PATH_AUTOMATION_ENABLED = 'productallocation/automation/config';
    const XML_PATH_AUTOMATION_ASSIGNTO_FIELD = 'productallocation/automation/assign_to';
    const XML_PATH_AUTOMATION_CUSTOMER_GROUP_FIELD = 'productallocation/automation/customer_group';
    protected $_storeManager;
    protected $_dataFactory;
    protected $scopeConfig;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Commercers\ProductAllocation\Helper\DataFactory $dataFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_dataFactory = $dataFactory;
        $this->_storeManager = $storeManagerInterface;
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
    public function isEnabledAutomation($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_AUTOMATION_ENABLED,$websiteId);
    }
    public function isFieldAssignToAutomation($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_AUTOMATION_ASSIGNTO_FIELD,$websiteId);
    }
    public function isFieldCustomerGroupAutomation($websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_AUTOMATION_CUSTOMER_GROUP_FIELD,$websiteId);
    }
}

